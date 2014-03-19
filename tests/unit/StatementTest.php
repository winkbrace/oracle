<?php

use Oracle\Connection;
use Oracle\Query\Statement;
use Oracle\Support\Config;

class StatementTest extends PHPUnit_Framework_TestCase
{
    /** @var Connection */
    protected $connection;

    public function setUp()
    {
        $this->connection = new Connection('TEST');
        Config::put('validate_sql_syntax', false);
    }

    public function tearDown()
    {
        unset($this->connection);
    }

    public function testCreation()
    {
        $statement = new Statement("select * from test", $this->connection);
        $this->assertInstanceOf('\\Oracle\\Query\\Statement', $statement);
    }

    public function testResource()
    {
        $statement = new Statement("select * from test", $this->connection);
        $resource = $statement->getResource();
        $this->assertTrue(is_resource($resource));
        $this->assertEquals('oci8 statement', get_resource_type($resource));
    }

    /**
     * test that sql is sanitized on construct
     */
    public function testSetSql()
    {
        $sql = "select *" . "\r\n\r\n" . "from" . "\r\n\r\n" . "user_tables";
        $statement = new Statement($sql, $this->connection);
        $sql = $statement->getSql();

        // \r\n should be replaced with \n everywhere
        $this->assertNotContains("\r\n", $sql);

        // remove empty lines
        $this->assertEquals("select *\nfrom\nuser_tables", $sql);
    }

    public function testBind()
    {
        $statement = new Statement("select * from test where id = :one", $this->connection);
        $string = $statement->toStringBindVariables();
        $this->assertSame('', $string);

        $statement->bind(array(
            ':one' => 1,
            ':two' => 2,
        ));

        $string = $statement->toStringBindVariables();
        $this->assertSame(':one => 1', trim($string));
    }

    /**
     * @expectedException \Oracle\OracleException
     */
    public function testInvalidBindVariable()
    {
        $statement = new Statement("select * from test where id = :one", $this->connection);
        $statement->bind(array(0 => 'foo')); // 0 could never be a valid bind variable
        $this->fail('Empty bind variable name should throw OracleException');
    }

    public function testInvalidSql()
    {
        $statement = new Statement("not a query", $this->connection);
        $this->assertFalse($statement->validateSqlSyntax());
    }

    public function testValidSql()
    {
        $statement = new Statement("select * from test", $this->connection);
        $this->assertTrue($statement->validateSqlSyntax());
    }

    public function testGetExplainPlan()
    {
        $statement = new Statement("select * from test", $this->connection);
        $plan = $statement->getExplainPlan();
        $this->assertStringStartsWith('Plan hash value:', $plan);
    }

    /**
     * @expectedException \Oracle\OracleException
     */
    public function testAutomaticallyValidateSql()
    {
        Config::put('validate_sql_syntax', true);
        new Statement("not a query", $this->connection);
        $this->fail('Invalid sql syntax should throw OracleException');
    }

    public function testGetStatementType()
    {
        $statement = new Statement("select * from test", $this->connection);
        $this->assertEquals('SELECT', $statement->getStatementType());

        $statement = new Statement("update test set value = 'hearty ' || value", $this->connection);
        $this->assertEquals('UPDATE', $statement->getStatementType());
    }

    public function testGetConnectionResource()
    {
        $statement = new Statement("select * from test", $this->connection);
        $resource = $statement->getConnectionResource();
        $this->assertEquals('oci8 connection', get_resource_type($resource));
    }

    public function testGetSchema()
    {
        $statement = new Statement("select * from test", $this->connection);
        $this->assertEquals('TEST', $statement->getSchema());
    }
}
