<?php namespace Oracle;

use Illuminate\Support\Collection;
use Oracle\Support\Config;

/**
 * Class Connection
 * @package Oracle
 *
 * This class is repsonsible for the connection to an oracle database
 */
class Connection
{
    /** database connection resource */
    protected $resource;
    /** @var string */
    protected $schema;
    /** @var string */
    protected $database;

    /**
     * Note that we don't establish the connection immediately. Connection only connects to the database when required.
     * And that is always when someone needs the connection resource
     *
     * @param string $schema
     * @param string $database
     * @throws \Exception
     */
    public function __construct($schema = null, $database = null)
    {
        $this->schema = $schema ?: Config::get('default_schema');
        $this->database = $database ?: Config::get('default_database');

        if (empty($this->schema) || empty($this->database))
            throw new \Exception('Unable to connect to database. Please provide schema and database or specify defaults in config.php.');
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        if (empty($this->resource))
            $this->connect();

        return $this->resource;
    }

    /**
     * open connection
     */
    protected function connect()
    {
        $schema = strtoupper($this->schema);
        $database = strtoupper($this->database);

        $credentials = new Collection(Config::get('credentials'));
        $users       = new Collection($credentials->get($database));
        $connections = new Collection(Config::get('connections'));

        $password   = $users->get($schema);
        $connection = $connections->get($database);

        $this->resource = oci_connect($schema, $password, $connection, 'UTF8');

        $this->setDefaultDateFormat();
    }

    /**
     * force a default date format no matter the latest reconfigurations our dba's execute on the databases
     * we set months to MON and not MM, so php's strtotime() will always understand then.
     */
    protected function setDefaultDateFormat()
    {
        $parsed = oci_parse($this->resource, "alter session set nls_date_format='DD-MON-YYYY HH24:MI:SS'");
        oci_execute($parsed);
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * close connection
     */
    public function __destruct()
    {
        if (is_resource($this->resource))
            oci_close($this->resource);

        unset($this->resource);
    }
}
