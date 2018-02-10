<?php
/**
 * file ProcessWrapper.php 2017/12/19
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

namespace App\Core\Common;

use App\Core\GA\Helpers\Algorithm;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;

declare(ticks = 1);
class ProcessWrapper
{
    const RUNNING_STATUS_RUNNING = 3;
    const RUNNING_STATUS_PAUSE = 2;
    const RUNNING_STATUS_STOP = 1;
    /**
     * @var Algorithm
     */
    protected $algorithm;

    protected $status = 0;

    protected $laravooleInfo;

    /**
     * @var \swoole_process
     */
    protected $process;

    public function __construct($laravooleInfo)
    {
        $this->laravooleInfo = $laravooleInfo;
    }

    public function getProcess()
    {
        return $this->process;
    }

    public function init($width, $height)
    {
        $this->algorithm = new Algorithm($width, $height);
        $this->process = new \swoole_process([$this, 'run'], $redirect_stdin_stdout = false, $create_pipe = true);
        $this->process->start();
        Log::debug('custom process[pid=' . $this->process->pid . '] started...');
    }

    public function listen()
    {
        Log::debug('called listen');
        $command = $this->process->read();
        Log::debug('read from pipe: ' . $command);

        $command = json_decode($command, true);
        $action = array_get($command, 'action', '');
        if (empty($command) || !is_array($command)) {
            Log::debug('invalid command return...');
            return;
        }

        $params = array_get($command, 'params', []);

        try {
            call_user_func_array([$this, $action], $params);
        } catch (\Exception $e) {
            Log::error('catch exception with message: ' . $e->getMessage() . ', with action=['.$action.'], params=['.json_encode($params).']');
        }
    }

    public static function command(\swoole_process $process, $action)
    {
        $process->write($action); // 同步
        $pid = $process->pid;
        Log::debug("write action[$action] to process[pid={$pid}]...");
        posix_kill($pid, SIGINT);
    }

    protected function flush($content)
    {
        $protocol = $this->laravooleInfo->codec;
        $data = $protocol::encode(
            Response::HTTP_OK,
            Request::METHOD_GET,
            $content,
            ''
        );
        $this->laravooleInfo->server->push($this->laravooleInfo->fd, $data);
    }

    public function run()
    {
        pcntl_signal(SIGINT, [$this, 'listen']);

        $this->status = self::RUNNING_STATUS_STOP;

        Log::debug('wrapper bootstrap...');

        while ($this->status >= self::RUNNING_STATUS_STOP) {

            $this->algorithm->GAInitialize();

            while ($this->status >= self::RUNNING_STATUS_PAUSE) {

                while ($this->status >= self::RUNNING_STATUS_RUNNING) {
                    $this->algorithm->GANextGeneration();
                    $params = $this->algorithm->getCurrentBestSolution();
                    $params['status'] = $this->status;
                    $this->flush($this->clientCallback('syncSolution', $params));

                    Log::debug('wrapper running..');
                    if (usleep(500000)) {
                        Log::debug('sleep interrupted..');
                    }
                }

                Log::debug('wrapper paused..');
                if (sleep(10)) {
                    Log::debug('sleep interrupted...');
                }
            }

            Log::debug('wrapper stoped..');
            if (sleep(15)) {
                Log::debug('sleep interrupted...');
            }
        }

        Log::debug('wrapper done...');
    }

    public function status($status)
    {
        Log::debug("change status: {$this->status} => {$status}");
        $this->status = $status;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->algorithm, $name)) {
            call_user_func_array([$this->algorithm, $name], $arguments);
        } else {
            throw new \BadMethodCallException('bad method: ' . $name);
        }

        if (in_array($name, ['addPoint', 'addRandomPoints', 'clearPoints'])) {
            $this->flush($this->clientCallback('updatePoints', ['points' => $this->algorithm->getPoints()]));
        }
    }

    /**
     * 给前端回调
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $callback
     * @param $params
     * @return array
     */
    protected function clientCallback($callback, $params)
    {
        return ['cb' => $callback, 'p' => $params];
    }
}