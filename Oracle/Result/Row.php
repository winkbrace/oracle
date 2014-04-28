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
     * Creates a new Row
     *
     * @see \Illuminate\Support\Collection::__construct()
     * @param array $items
     * @return void
     */
    public function __construct(array $items = array())
    {
        parent::__construct(array_change_key_case($items, CASE_UPPER));
    }

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

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->items[strtoupper($key)]);
    }

    /**
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->items[strtoupper($key)]);
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
