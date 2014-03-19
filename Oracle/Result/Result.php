<?php namespace Oracle\Result;

use Illuminate\Support\Collection;

/**
 * The Result class contains all the rows.
 * It is responsible for access to the rows and the column meta data
 */
class Result extends Collection
{
    /** @var Row[] */
    protected $items = array();

}
