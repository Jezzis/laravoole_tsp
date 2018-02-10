<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getLaravooleBuffer($key = '')
    {
        $server = $this->getLaravooleServer();
        $fd = $this->getLaravooleFd();
        Log::debug(sprintf('server[#%s] with fd[=%d] get key[=%s]', spl_object_hash($server), $fd, $key));
        return get_laravoole_buffer($server, $fd, $key);
    }

    public function setLaravooleBuffer($key, $value)
    {
        $server = $this->getLaravooleServer();
        $fd = $this->getLaravooleFd();
        Log::debug(sprintf('server[#%s] with fd[=%d] set key[=%s], value[=%s]', spl_object_hash($server), $fd, $key, json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
        set_laravoole_buffer($server, $fd, $key, $value);
    }

    public function getLaravooleInfo()
    {
        $request = app('request');
        return $request->getLaravooleInfo();
    }

    public function getLaravooleFd()
    {
        $laravooleInfo = $this->getLaravooleInfo();
        if (!empty($laravooleInfo)) {
            return (int) $laravooleInfo->fd;
        }
        return false;
    }

    /**
     * @author Jezzis <jezzis727@126.com>
     *
     * @return \swoole_websocket_server
     */
    public function getLaravooleServer()
    {
        $laravooleInfo = $this->getLaravooleInfo();
        if (!empty($laravooleInfo)) {
            return $laravooleInfo->server;
        }
        return false;
    }

    public function getLaravooleCodec()
    {
        $laravooleInfo = $this->getLaravooleInfo();
        if (!empty($laravooleInfo)) {
            return $laravooleInfo->codec;
        }
        return false;
    }

    public function success($data = [])
    {
        return json_encode(['data' => $data, 'msg' => 'success', 'code' => 0]);
    }
}
