<?php
/**
 * file PointFactory.php 2017/12/5
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

namespace App\Core\Common\Helpers;


class PointFactory
{
    const TYPE_2D = '2d';
    const TYPE_GEO = 'geo';

    /**
     * new instance
     * @author Jezzis <jezzis727@126.com>
     *
     * @param $type
     * @param $x
     * @param $y
     * @return Point
     */
    public static function create($type, $x, $y)
    {
        switch ($type) {
            case self::TYPE_2D:
                return Point2D::newInstance($x, $y);
            case self::TYPE_GEO:
                return PointGEO::newInstance($x, $y);
        }
        throw new \InvalidArgumentException('invalid param[type]: ' . $type);
    }
}