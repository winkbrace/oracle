<?php

use Oracle\Connection;
use Oracle\Support\Config;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Connection */
    protected $conn;

    public function setUp()
    {
        $this->conn = new Connection();
    }

    public function tearDown()
    {
        unset($this->conn);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Connection', $this->conn);
    }

    public function testResource()
    {
        $resource = $this->conn->getResource();
        $this->assertTrue(is_resource($resource));
        $this->assertEquals('oci8 connection', get_resource_type($resource));
    }

    /**
     * @group database
     */
    public function testDefaultDateFormat()
    {
        $dateFormat = $this->fetchDateFormat();
        $this->assertEquals('DD-MON-YYYY HH24:MI:SS', $dateFormat);
    }

    protected function fetchDateFormat()
    {
        $sql = "select value from v\$nls_parameters where parameter = 'NLS_DATE_FORMAT'";
        $query = oci_parse($this->conn->getResource(), $sql);
        oci_execute($query);
        $row = oci_fetch_assoc($query);
        oci_free_statement($query);
        return $row['VALUE'];
    }

    public function testDefaultConnection()
    {
        $conn = new Connection();
        $defaultSchema = Config::get('default_schema');
        $defaultDatabase = Config::get('default_database');
        $this->assertEquals($defaultSchema, $conn->getSchema());
        $this->assertEquals($defaultDatabase, $conn->getDatabase());
    }

    public function testIndifferentConnectionNames()
    {
        $database = strtolower(Config::get('default_database'));
        $conn = new Connection('test', $database);
        $this->assertEquals('test', $conn->getSchema());
        $this->assertEquals($database, $conn->getDatabase());

        $resource = $conn->getResource();
        $this->assertTrue(is_resource($resource));
        $this->assertEquals('oci8 connection', get_resource_type($resource));
    }

}
