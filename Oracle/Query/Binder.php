<?php namespace Oracle\Query;

use Oracle\OracleException;

/**
 * Class Binder
 * This class is responsible for binding variables to the statement
 */
class Binder
{
    /** @var \Oracle\Query\Statement  */
    protected $statement;
    /** @var array */
    protected $bindVariables = array();
    /** @var array */
    protected $outParameters = array();


    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * bind variables to query statement
     * @param array $binds
     * @throws \InvalidArgumentException
     */
    public function bind(array $binds)
    {
        foreach ($binds as $bind => $value)
        {
            if (empty($bind))
                throw new \InvalidArgumentException('Empty bind variable given with value '.$value);

            // ensure leading ':'
            $bind = ':' . ltrim($bind, ':');

            // With this check, we can use the binds for all reports without the need to define
            // which bind variables to use for which reports
            if (! $this->bindVariableUsedInSql($bind))
                continue;

            if (is_array($value))
                $this->bindArray($bind, $value);
            else
                $this->bindValue($bind, $value);
        }
    }

    /**
     * bind a value to a bind variable
     * @param $bind
     * @param $value
     * @throws OracleException
     */
    protected function bindValue($bind, $value)
    {
        // store bind info in array for possible later use (num_rows)
        $this->bindVariables[$bind] = $value;

        $result = oci_bind_by_name($this->statement->getResource(), $bind, $value, -1);
        if ($result === false)
            throw new OracleException('Error binding value to bind variable');
    }

    /**
     * bind array of values
     *
     * example: where field in (:list)
     *
     * @param string $bind
     * @param array $array
     */
    protected function bindArray($bind, $array)
    {
        if (empty($array))
            return;

        if (count($array) === 1)
        {
            $this->bindValue($bind, current($array));
            return;
        }

        // create array of bind variables for each entry in $array
        $binds = array();
        foreach ($array as $key => $val)
            $binds[$bind.$key] = $val;

        // create new sql where we replace the one bind variable with the list of bind variables
        $sql = str_replace($bind, implode(', ', array_keys($binds)), $this->statement->getSql());
        $this->statement->setSql($sql);

        // don't forget to re-bind previously bound variables
        $binds = array_merge($binds, $this->bindVariables);

        // bind all the new bind variables
        $this->bind($binds);
    }

    /**
     * Check if the bind variable exists in the sql
     * With this check, we can use the binds for all reports without the need to define
     * which bind variables to use for which reports
     *
     * @param string $bind
     * @return boolean
     */
    protected function bindVariableUsedInSql($bind)
    {
        $sql = $this->statement->getSql();

        if (strpos($sql, $bind) === false)
            return false;

        // strip the ':' which is a word boundary character
        $checkBind = ltrim($bind, ':');

        // make sure the bind variable is not part of another bind variable (e.g. :reseller vs. :resellersoort)
        $pattern = '/:\b' . $checkBind . '\b/i';

        return preg_match($pattern, $sql) === 1;
    }

    /**
     * @return array $bindVariables
     */
    public function getBindVariables()
    {
        return $this->bindVariables;
    }

    /**
     * return the bind variables as a string
     */
    public function toStringBindVariables()
    {
        $string = '';
        if (! empty($this->bindVariables))
        {
            foreach ($this->bindVariables as $bind => $val)
                $string .= $bind . ' => ' . $val . "\n";
        }

        return $string;
    }

    /**
     * Bind a variable for a procedure with an OUT parameter (size is required)
     * the out parameter can be fetched with getOutParameter($name)
     *
     * @param string $bind
     * @param string $name
     * @param int $size
     *
     * @return bool success
     */
    public function bindOutParameter($bind, $name, $size)
    {
        return oci_bind_by_name($this->statement->getResource(), $bind, $this->outParameters[$name], $size);
    }

    /**
     * @return array $outParameters
     */
    public function getOutParameters()
    {
        return $this->outParameters;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getOutParameter($name)
    {
        return $this->outParameters[$name];
    }

}
