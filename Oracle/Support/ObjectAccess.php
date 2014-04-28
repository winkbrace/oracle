<?php namespace Oracle\Support;

interface ObjectAccess
{
    public function __get($key);

    public function __set($key, $value);

    public function __isset($key);

    public function __unset($key);
}
