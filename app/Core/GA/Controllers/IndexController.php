<?php

namespace App\Core\GA\Controllers;

use App\Core\Common\ProcessWrapper;
use App\Core\GA\Helpers\Algorithm;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Log;

class IndexController extends Controller
{
    /**
     * @author Jezzis <jezzis727@126.com>
     *
     * @return ProcessWrapper
     */
    protected function getProcessor()
    {
        return $this->getLaravooleBuffer('processor');
    }

    /**
     *
     * @author Jezzis <jezzis727@126.com>
     *
     * @return Algorithm
     */
    protected function getAlgorithm()
    {
        return $this->getLaravooleBuffer('algorithm');
    }

    public function init(Request $request)
    {
        $processor = new ProcessWrapper($this->getLaravooleInfo());

        $height = $request->input('h');
        $width = $request->input('w');
        $processor->init($width, $height);
        $this->setLaravooleBuffer('processor', $processor);

        $this->success();
    }

    public function addRandomPoints(Request $request)
    {
        $num = (int) $request->input('num', 50) ?: 50;
        $this->proxy('addRandomPoints', [$num]);
        return $this->success();
    }

    public function addPoint(Request $request)
    {
        $x = $request->input('x');
        $y = $request->input('y');
        $this->proxy('addPoint', [$x, $y]);
        return $this->success();
    }

    public function start()
    {
        $this->proxy('status', [ProcessWrapper::RUNNING_STATUS_RUNNING]);
    }

    public function stop()
    {
        $this->proxy('status', [ProcessWrapper::RUNNING_STATUS_PAUSE]);
    }

    protected function proxy($action, $params = [])
    {
        $processor = $this->getProcessor();
        $command = json_encode(['action' => $action, 'params' => $params]);
        ProcessWrapper::command($processor->getProcess(), $command);
    }
}
