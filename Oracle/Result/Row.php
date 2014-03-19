<?php namespace Oracle\Result;

use Illuminate\Support\Collection;
use Oracle\Support\ObjectAccess;

/**
 * The Row class contains the result of a fetched row
 * It is responsible for access to the data in that row.
 */
class Row extends Collection implements ObjectAccess
{
    /**
     * Get an item from the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return parent::get(strtoupper($key), $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function put($key, $value)
    {
        parent::put(strtoupper($key), $value);
    }

// ObjectAccess //////////////////////////////////////////////////

    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->put($key, $value);
    }

// ArrayAccess //////////////////////////////////////////////////

	public function offsetExists($key)
    {
        return parent::offsetExists(strtoupper($key));
    }

	public function offsetGet($key)
    {
        return parent::offsetGet(strtoupper($key));
    }

	public function offsetSet($key, $value)
    {
        parent::offsetSet(strtoupper($key), $value);
    }

	public function offsetUnset($key)
    {
        parent::offsetUnset(strtoupper($key));
    }
}
