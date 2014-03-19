<?php namespace Oracle\Query;

use Oracle\Log\QueryLogger;
use Oracle\Support\Config;

/**
 * This class is responsible for executing queries
 */
class Executor
{
    const COMMIT = true;
    const NO_COMMIT = false;


    /** @var \Oracle\Query\Statement */
    protected $statement;
    /** @var bool */
    protected $isExecuted = false;
    /** @var QueryLogger */
    protected $logger;

    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;

        if (Config::get('logging') === true)
            $this->setLogger(Config::get('logger'));
    }

    /**
     * @param QueryLogger $logger
     */
    public function setLogger(QueryLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param bool $commit
     * @return bool $isExecuted
     */
    public function execute($commit = self::COMMIT)
    {
        if (! $this->isExecuted)
        {
            $startTime = microtime(true);

            $flag = $commit === true ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT;
            $this->isExecuted = oci_execute($this->statement->getResource(), $flag);

            $this->log($startTime);
        }

        return $this->isExecuted;
    }

    /**
     * Voer insert uit en return last inserted id
     *
     * @param string $sequence
     * @param bool $commit
     * @return int|false $lastId
     */
    public function insert($sequence = null, $commit = self::COMMIT)
    {
        if ($this->statement->getStatementType() != 'INSERT')
            return false;

        if (empty($sequence))
            return false;

        if (! $this->execute($commit))
            return false;

        // try to return the currval of the given sequence
        $resource = oci_parse($this->statement->getConnectionResource(), "select ".$sequence.".currval cv from dual");
        oci_define_by_name($resource, 'CV', $lastId);

        $flag = $commit === true ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT;
        if (! oci_execute($resource, $flag))
            return false;

        oci_fetch($resource);

        return $lastId;
    }


    /**
     * Binding once for multiple executions
     * Especially handy when you want to read a file into the database.
     * (see outboundResults.php for an example)
     *
     * This way of binding requires you specify the max string length of the columns
     *
     * @param array $binds
     * @param array $sizes
     * @param array $data
     * @param bool  $commit
     * @return int $errorCount
     */
    public function executeMultiple($binds, $sizes, $data, $commit = self::COMMIT)
    {
        $errorCount = 0;

        // first determine all binds once
        foreach ($binds as $i => $bind)
        {
            // ${trim($bind, ':')} example:  :actie_id -> $actie_id
            oci_bind_by_name($this->statement->getResource(), $bind, ${trim($bind, ':')}, $sizes[$i]);
        }

        // Then loop over all rows and give the variables the new value for that row
        // This is because the variables remain bound!
        for ($row = 0; $row < count($data); $row++)
        {
            foreach ($binds as $i => $bind)
            {
                $value = array_key_exists($i, $data[$row]) ? substr($data[$row][$i], 0, $sizes[$i]) : null;
                ${trim($bind, ':')} = trim($value);
            }

            if (! @oci_execute($this->statement->getResource(), OCI_DEFAULT))  // don't commit after each row
                $errorCount++;
        }

        if ($commit)
        {
            $this->commit();
        }

        return $errorCount;
    }

    /**
     * @param number $startTime microtime
     */
    protected function log($startTime)
    {
        if (! empty($this->logger))
        {
            $this->logger->log(
                $startTime,
                $this->statement->getSql(),
                $this->statement->toStringBindVariables()
            );
        }
    }

    /**
     * @return boolean
     */
    public function isExecuted()
    {
        return $this->isExecuted;
    }

    /**
     * Commit
     */
    public function commit()
    {
        return oci_commit($this->statement->getConnectionResource());
    }

    /**
     * Rollback
     */
    public function rollback()
    {
        return oci_rollback($this->statement->getConnectionResource());
    }

}
