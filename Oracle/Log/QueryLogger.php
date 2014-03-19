<?php namespace Oracle\Log;

interface QueryLogger
{
    /**
     *
     * @param number $startTime
     * @param string $sql
     * @param string $bindVariableString
     */
    public function log($startTime, $sql, $bindVariableString);
}
