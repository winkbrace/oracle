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
