<?php

use Oracle\Support\Config;
use Oracle\Log\WebQueryLog;

class WebQueryLogTest extends PHPUnit_Framework_TestCase
{
    /** @var WebQueryLog */
    protected $logger;

    public function setUp()
    {
        Config::put('logging', true);
        require_once realpath(__DIR__ . '/../../bootstrap.php'); // creates Logger and puts it in Config
        $this->logger = Config::get('logger');
    }

    public function tearDown()
    {
        Config::put('logging', false);
        unset($this->logger);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Log\\QueryLogger', $this->logger);
        $this->assertInstanceOf('\\Oracle\\Log\\WebQueryLog', $this->logger);
    }

    /**
     * @group database
     */
    public function testLogging()
    {
        $startTime = microtime(true) - 1000;
        $sql = "select * from dual";
        $bindVariablesString = ":one => 1, :test => 'test'";
        $this->logger->log($startTime, $sql, $bindVariablesString);

        // check if record was inserted
        $conn = new \Oracle\Connection('test');

        $sql = "select count(*) amount from web_query_log where bind_vars = ':one => 1, :test => ''test''' and ip is null and start_time > trunc(sysdate)";
        $resource = oci_parse($conn->getResource(), $sql);
        oci_execute($resource);
        $row = oci_fetch_assoc($resource);
        oci_free_statement($resource);

        $this->assertGreaterThan(0, $row['AMOUNT']);

        // remove this log record for the next test
        $sql = "delete from web_query_log where bind_vars = ':one => 1, :test => ''test''' and ip is null and start_time > trunc(sysdate)";
        $resource = oci_parse($conn->getResource(), $sql);
        oci_execute($resource);
        oci_free_statement($resource);
    }
}
