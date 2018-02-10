<?php

namespace App\Core\Common\Helpers;


abstract class Point
{
    public $x;
    public $y;

    /**
     * 计算距离
     * @author Jezzis <jezzis727@126.com>
     *
     * @param Point $point
     * @return int
     */
    abstract function distanceTo(Point $point);

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    static function newInstance($x, $y)
    {
        return new static($x, $y);
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function setX($x)
    {
        $this->x = $x;
    }

    public function setY($y)
    {
        $this->y = $y;
    }

}