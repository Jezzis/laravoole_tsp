<?php
/**
 * file laravoole_helper.php 2017/12/26
 *
 * This file is part of the tsp
 *
 * @author Jezzis <jezzis727@126.com>
 * @copyright (c) 2014 - 2017 Yunniao Inc.
 */

use Swoole\Http\Server as swoole_server;

if (!function_exists('get_laravoole_buffer')) {
    function get_laravoole_buffer(swoole_server $server, $fd, $key)
    {
        if (!empty($server) && !empty($fd)) {
            empty($server->buffer) && $server->buffer = [];
            if (empty($server->buffer[$fd]))
                $server->buffer[$fd] = [];
            $data = $server->buffer[$fd];
            if (!empty($key)) {
                return array_get($data, $key, '');
            }
            return $data;
        }
        throw new \InvalidArgumentException('empty server or fd: ' . $fd);
    }
}

if (!function_exists('set_laravoole_buffer')) {
    function set_laravoole_buffer(swoole_server $server, $fd, $key, $value)
    {
        if (!empty($server) && !empty($fd)) {
            empty($server->buffer) && $server->buffer = [];
            if (empty($server->buffer[$fd]))
                $server->buffer[$fd] = [];
            array_set($server->buffer[$fd], $key, $value);
            return;
        }
        throw new \Exception('empty server or fd: ' . $fd);
    }
}
