<?php namespace Oracle\Query;

use Oracle\Connection;
use Oracle\Dump\Error;
use Oracle\OracleException;
use Oracle\Result\Result;
use Oracle\Support\Config;

/**
 * Class Statement
 * This class is responsible for creating the query statement
 */
class Statement
{
    /** @var Connection */
    protected $connection;
    /** @var string */
    protected $sql;
    /** @var resource */
    protected $resource;
    /** @var Executor */
    protected $executor;
    /** @var Binder */
    protected $binder;
    /** @var Result */
    protected $result;


    /**
     * @param string $sql
     * @param Connection $connection
     */
    public function __construct($sql, Connection $connection)
    {
        $this->connection = $connection;
        $this->setSql($sql);
        $this->binder = new Binder($this);
        $this->executor = new Executor($this);
    }

    /**
     * set sql
     * @param string $sql
     */
    public function setSql($sql)
    {
        $this->sql = $this->removeBlankLines($sql);

        // I cannot think of a reason to not immediately parse the sql, so we always parse on setSql
        $this->parse();
    }

    /**
     * @param $sql
     * @return string
     */
    protected function removeBlankLines($sql)
    {
        $sql = str_replace("\r\n", "\n", $sql);

        // walk through every line in the sql and remove the empty lines
        // this will make it possible to create cleaner dynamic sql in our php scripts
        $lines = explode("\n", $sql);
        $lines = array_filter($lines, function($val) {
            return strlen(trim($val)) > 0;
        });

        return implode("\n", $lines);
    }

    /**
     * parse the sql statement into an oci8 statement resource
     */
    protected function parse()
    {
        if ($this->hasStatementResource())
            oci_free_statement($this->resource);

        $this->resource = oci_parse($this->connection->getResource(), $this->sql);

        // unfortunately oci_parse cannot check the sql statement, so I am unsure when the resource would be false.
        if (! $this->resource)
            throw new OracleException('Unable to parse sql statement.');

        // check sql syntax if this configuration is enabled
        if (Config::get('validate_sql_syntax') === true)
        {
            if (! $this->validateSqlSyntax())
                throw new OracleException('Invalid sql statement.');
        }
    }

    /**
     * check the sql syntax by running the explain plan on the query.
     *
     * @return bool
     */
    public function validateSqlSyntax()
    {
        return $this->createExplainPlan();
    }

    /**
     * get the explain plan for the query.
     * Returns null when the sql is invalid.
     * @return array|null
     */
    public function getExplainPlan()
    {
        $result = $this->createExplainPlan();
        if ($result === false)
            return null;

        return $this->fetchExplainPlan();
    }

    /**
     * @return bool
     */
    protected function createExplainPlan()
    {
        $sql = "explain plan for " . $this->sql;
        $resource = oci_parse($this->connection->getResource(), $sql);

        return @oci_execute($resource); // do not throw the error, but return false
    }

    /**
     * Note: running this query takes about 1 second
     * @return string
     */
    protected function fetchExplainPlan()
    {
        $sql = "select * from table(dbms_xplan.display)";
        $resource = oci_parse($this->connection->getResource(), $sql);
        oci_execute($resource);
        oci_fetch_all($resource, $result);
        return implode(PHP_EOL, $result['PLAN_TABLE_OUTPUT']);
    }

    /**
     * @return resource
     */
    public function getConnectionResource()
    {
        return $this->connection->getResource();
    }

    /**
     * @return bool
     */
    protected function hasStatementResource()
    {
        return (is_resource($this->resource) && 'oci8 statement' == get_resource_type($this->resource));
    }

    /**
     * @return string
     */
    public function getStatementType()
    {
        return oci_statement_type($this->resource);
    }

    /**
     * @param bool $commit
     * @return bool
     */
    public function execute($commit = Executor::COMMIT)
    {
        if ($this->isExecuted())
            return true;

        $this->executor = new Executor($this);

        return $this->executor->execute($commit);
    }

    /**
     * @param array $binds
     * @throws OracleException
     */
    public function bind(array $binds)
    {
        try
        {
            $this->binder->bind($binds);
        }
        catch (\Exception $e)
        {
            throw new OracleException($this->getError($e->getMessage()));
        }
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->connection->getSchema();
    }

    /**
     * @return bool
     */
    public function isExecuted()
    {
        if (empty($this->executor))
            return false;

        return $this->executor->isExecuted();
    }

    /**
     * @return string
     */
    public function toStringBindVariables()
    {
        return $this->binder->toStringBindVariables();
    }

    /**
     * return only the Oracle error message
     *
     * @param string $message
     * @return string
     */
    public function getErrorMessage($message = '')
    {
        $error = new Error($this, $message);
        return $error->getErrorMessage();
    }

    /**
     * returns last error found
     * @param string $message    optional message to prepend
     * @return string
     */
    public function getError($message = '')
    {
        $error = new Error($this, $message);
        return $error->render();
    }

    /**
     * get string representation of this object
     * @return string
     */
    function __toString()
    {
        return $this->sql. "\n\n" . $this->toStringBindVariables();
    }

    /**
     * free oci8 connection resource
     */
    public function __destruct()
    {
        if ($this->hasStatementResource())
        {
            oci_free_statement($this->resource);
        }

        unset($this->resource);
    }

}
