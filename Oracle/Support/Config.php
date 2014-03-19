<?php namespace Oracle\Support;

/**
 * Config.php
 * Grants access to the contents of the config.php file
 */

class Config
{
    /** @var array */
    protected static $config = array();
    /** @var bool */
    protected static $fileLoaded = false;

    /**
     * get the contents of the config.php file as array
     * @return array
     */
    protected static function getConfig()
    {
        if (! static::$fileLoaded)
        {
            $config = require realpath(__DIR__ . '/../../config.php');
            static::$config = array_merge($config, static::$config);
            static::$fileLoaded = true;
        }

        return static::$config;
    }

    /**
     * get config setting belonging to key
     * @param $key
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function get($key)
    {
        $config = static::getConfig();
        if (! array_key_exists($key, $config))
            throw new \InvalidArgumentException('given key "'. $key .'" does not exist in config.php');

        return $config[$key];
    }

    /**
     * get config array
     * @return array
     */
    public static function all()
    {
        return static::getConfig();
    }

    /**
     * put new value in the Config
     * @param $key
     * @param $value
     */
    public static function put($key, $value)
    {
        static::$config[$key] = $value;
    }
}
