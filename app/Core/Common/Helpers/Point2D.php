<?php
/**
 * file Point2D.php 2017/12/4
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

namespace App\Core\Common\Helpers;

class Point2D extends Point
{
    public function distanceTo(Point $point)
    {
        return (int) sqrt(pow($this->getX() - $point->getX(), 2) + pow($this->getY() - $point->getY(), 2));
    }
}