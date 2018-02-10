<?php
/**
 * file array_util.php 2017/12/12
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

use Illuminate\Support\Arr;

if (! function_exists('array_index_of')) {
    /**
     * Return the first element in an array equals to the given value.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @return mixed
     */
    function array_index_of($array, $value)
    {
        if (!Arr::accessible($array)) {
            return false;
        }

        $index = false;
        foreach ($array as $idx => $item) {
            if ($value == $item) {
                $index = $idx;
                break;
            }
        }
        return $index;
    }
}

if (! function_exists('array_remove_of')) {
    /**
     * remove an element in an array equals to the given value.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @return void
     */
    function array_remove_of(&$array, $value)
    {
        $index = array_index_of($array, $value);
        if ($index !== false) {
            unset($array[$index]);
            $array = array_values($array);
        }
    }
}

if (! function_exists('array_next')) {
    /**
     * get the element in an array next to the given index
     *
     * @param  array  $array
     * @param  int  $index
     * @return mixed
     */
    function array_next($array, $index)
    {
        if (!Arr::accessible($array) || empty($array)) {
            return value($array);
        }
        $index = ($index + 1) % count($array);
        return array_get($array, $index);
    }
}

if (! function_exists('array_prev')) {
    /**
     * get the element in an array prev to the given index
     *
     * @param  array  $array
     * @param  int  $index
     * @return mixed
     */
    function array_prev($array, $index)
    {
        if (!Arr::accessible($array) || empty($array)) {
            return value($array);
        }
        $index = (count($array) + $index - 1) % count($array);
        return array_get($array, $index);
    }
}

if (! function_exists('array_swap')) {
    /**
     * swap two elements in array by passing two indexes.
     *
     * @param  array  $array
     * @param  int  $x
     * @param  int  $y
     * @return void
     */
    function array_swap(&$array, $x, $y)
    {
        if (Arr::accessible($array)) {
            $tmp = $array[$x];
            $array[$x] = $array[$y];
            $array[$y] = $tmp;
        }
    }
}

if (! function_exists('array_roll')) {
    /**
     * roll an an array by generate an random index
     *
     * @param  array  $array
     * @return array
     */
    function array_roll($array)
    {
        if (Arr::accessible($array)) {
            $length = count($array);
            $randIndex = rand($array, 0, $length);

            $temp = [];
            for ($i = $randIndex; $i < $length; $i++)
                $temp[] = $array[$i];
            for ($i = 0; $i < $randIndex; $i++)
                $temp[] = $array[$i];
            return $temp;
        }

        return false;
    }
}
