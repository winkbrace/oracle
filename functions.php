<?php
if (! function_exists('initcap'))
{
    /**
     * copy Oracle's initcap function. php doesn't lowercase the rest by default with ucfirst.
     * @param string $string
     * @return string
     */
    function initcap($string)
    {
        return ucfirst(strtolower($string));
    }
}

if (! function_exists('arrayToObject'))
{
    /**
     * convert any array to an object
     * I like this better than simply type casting, because this will convert the keys to lowercase
     * and can convert multidimensional arrays through recursion.
     *
     * @param array $array
     * @return stdClass|null
     */
    function arrayToObject($array)
    {
        if(! is_array($array))
            return $array;

        $object = new stdClass();
        if (is_array($array) && count($array) > 0)
        {
            foreach ($array as $name => $value)
            {
                $name = strtolower(trim($name));
                if (! empty($name))
                    $object->$name = arrayToObject($value);
            }

            return $object;
        }
        else
        {
            return null;
        }
    }
}

if (! function_exists('working_locally'))
{
    /**
     * Checks the environment: development, test or production
     * @return boolean true if it's running locally
     */
    function working_locally()
    {
        if (getenv('ENVIRONMENT') == 'development')
            return true;

        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'localhost')
            return true;

        if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1')
            return true;

        // running from cli on windows
        if (isset($_SERVER['COMPUTERNAME']))
            return true;

        return false;
    }
}

if (! function_exists('vd'))
{
    /**
     * var_dump & die
     * @param mixed $var
     */
    function vd($var)
    {
        array_map(function($x) { var_dump($x); }, func_get_args());
        die;
    }
}

if (! function_exists('instr'))
{
    /**
     * custom function for checking if needle exists in haystack. This saves me the trouble of checking with !== false everywhere
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    function instr($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}
