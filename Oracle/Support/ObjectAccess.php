<?php namespace Oracle\Support;

interface ObjectAccess
{
    public function __get($key);

    public function __set($key, $value);
}
