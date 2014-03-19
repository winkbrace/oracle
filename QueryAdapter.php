<?php
use Oracle\Connection;
use Oracle\Export\FlatFileSender;
use Oracle\Export\FlatFileStreamer;
use Oracle\Export\FlatFileWriter;
use Oracle\LazyLoader;
use Oracle\Query\Executor;
use Oracle\Query\Fetcher;
use Oracle\Query\Statement;
use Oracle\Result\Result;
use Oracle\Result\Row;
use Oracle\Support\Config;

/**
 * This class can be used as an Adapter to the Oracle library
 *
 * Use it as a boilerplate and create your own Query class if you like to.
 */
class QueryAdapter
{
    /** @var Connection */
    protected $connection;
    /** @var Statement */
    protected $statement;
    /** @var LazyLoader */
    protected $loader;


    /**
     * @param string $sql
     * @param string $schema
     * @param string $database
     */
    public function __construct($sql, $schema = null, $database = null)
    {
        $schema = $schema ?: Config::get('default_schema');
        $database = $database ?: Config::get('default_database');

        $this->connection = new Connection($schema, $database);
        $this->statement = new Statement($sql, $this->connection);
        $this->loader = new LazyLoader($this->statement);
    }

    /**
     * @param array $binds
     */
    public function bind(array $binds)
    {
        $this->loader->loadBinder()->bind($binds);
    }

    /**
     * @param bool $commit
     */
    public function execute($commit = true)
    {
        $this->loader->loadExecutor()->execute($this->translateCommitConstant($commit));
    }

    /**
     * @param int $type
     * @return Row
     */
    public function fetch($type = Fetcher::FETCH_ASSOC)
    {
        return $this->loader->loadFetcher()->fetch($type);
    }

    /**
     * @param int $type
     * @return Result
     */
    public function fetchAll($type = Fetcher::FETCH_ASSOC)
    {
        return $this->loader->loadFetcher()->fetchAll($type);
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->loader->loadExecutor()->commit();
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        return $this->loader->loadExecutor()->rollback();
    }

    /**
     * return just the Oracle error message
     *
     * @param string $message
     * @return string error message
     */
    public function getErrorMessage($message = '')
    {
        return $this->loader->loadError($message)->getErrorMessage();
    }

    /**
     * returns last error found
     * @param string $message    optional message to prepend
     * @param bool   $inErrorDiv if return string should be encapsulated by an error div
     * @return null|string
     */
    public function getError($message = '', $inErrorDiv = true)
    {
        return $this->loader->loadError($message)->render();
    }

}
