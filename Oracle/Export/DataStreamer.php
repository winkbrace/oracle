<?php namespace Oracle\Export;

use Oracle\Query\Fetcher;

abstract class DataStreamer
{
    /** @var Fetcher */
    protected $fetcher;

    /**
     * @return string|false
     */
    abstract public function next();
}
