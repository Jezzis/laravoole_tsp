<?php
/**
 * file Scene.php 2017/12/4
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

namespace App\Core\Common\Helpers;


use Log;
use Illuminate\Support\Arr;

class Scene
{
    protected $width, $height;

    protected $pointType;

    /**
     * @var array [Point]
     */
    protected $points = [];

    protected $distances = [];

    private $pointCount = 0;

    public function __construct($width, $height, $type)
    {
        $this->width = $width;
        $this->height = $height;
        $this->pointType = $type;
        $this->clearPoints();
    }

    public function addRandomPoints($size = 10)
    {
        $this->addPoints($this->makeRandomPoints($size));
    }

    public function makeRandomPoints($size = 1) {
        $points = [];
        while ($size -- > 0) {
            $x = rand(1, $this->width - 1);
            $y = rand(1, $this->height - 1);
            $points[] = $this->makePoint($x, $y);
        };
        return $points;
    }

    /**
     * make point by coordinates
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $x
     * @param $y
     * @return Point
     */
    protected function makePoint($x, $y)
    {
        $x = (int) min($x, $this->width);
        $y = (int) min($y, $this->height);
        return PointFactory::create($this->pointType, $x, $y);
    }

    public function addPoint($x, $y)
    {
        $this->addPoints($this->makePoint($x, $y));
    }

    /**
     * add single point or multi points
     * @author Jezzis <jezzis727@126.com>
     *
     * @param array|Point $points
     */
    public function addPoints($points)
    {
        if ($points instanceof Point) {
            $points = [$points];
        }

        if (!Arr::accessible($points)
            || !empty(array_first($points, function($point) { return !is_object($point) || !$point instanceof Point; }))) {
            throw new \InvalidArgumentException('invalid param points');
        }

        $this->points = array_merge($this->points, $points);
        $this->pointCount = count($this->points);
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function setPoints(array $points)
    {
        $this->clearPoints();
        $this->addPoints($points);
    }

    /**
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $index
     * @return Point
     */
    public function getPoint($index)
    {
        return array_get($this->points, $index);
    }

    public function getPointCount()
    {
        return $this->pointCount;
    }

    public function clearPoints()
    {
        $this->points = [];
        $this->pointCount = 0;
    }

    public function calcDistance()
    {
        $this->distances = [];
        for ($i = 0; $i < $this->getPointCount(); $i ++) {
            $this->distances[$i] = [];
            for ($j = 0; $j < $this->getPointCount(); $j++) {
                $this->distances[$i][$j] = $i == $j ? 0 : $this->getPoint($i)->distanceTo($this->getPoint($j));
                Log::debug(sprintf('point %d[%d, %d] => point %d[%d, %d]: %d', $i, $this->getPoint($i)->getX(), $this->getPoint($i)->getY(),
                    $j, $this->getPoint($j)->getX(), $this->getPoint($j)->getY(), $this->distances[$i][$j]));
            }
        }
        Log::debug('distance: ' . json_encode($this->distances));
    }

    public function getDistance($x, $y)
    {
        return $this->distances[$x][$y];
    }

}