<?php

use Oracle\Connection;
use Oracle\Dump\Error;
use Oracle\Query\Statement;

// normally our error handler would log the error and continue rendering the page.
// For these tests we have to simulate that behaviour, or our Error class will never get called, because
// codeception will catch the error as an ErrorException
function test_error_handler($severity, $message, $filename, $line)
{
    echo PHP_EOL . 'Ignoring error of level ' . $severity . ': ' . $message . ' in ' . $filename . ' at line ' . $line . PHP_EOL;
}

define('ACCOUNT_LEVEL', 'ADMIN'); // required to display the errors


class ErrorTest extends PHPUnit_Framework_TestCase
{
    protected $connection;

    public function setUp()
    {
        $this->connection = new Connection('TEST');

        set_error_handler('test_error_handler');
    }

    public function tearDown()
    {
        unset($this->connection);
    }

    public function testCreation()
    {
        $sql = "select * from invalid_table_name";
        $statement = new Statement($sql, $this->connection);
        $error = new Error($statement);
        $this->assertInstanceOf('\\Oracle\\Dump\\Error', $error);
    }

    public function testErrorMessage()
    {
        $sql = "select * from invalid_table_name";
        $error = $this->runStatementToFail($sql);

        $actual = $error->getErrorMessage();
        $this->assertContains('table or view does not exist', $actual);
    }

    public function testCustomErrorMessage()
    {
        $sql = "select * from invalid_table_name";
        $statement = new Statement($sql, $this->connection);
        $statement->execute();
        $error = new Error($statement, 'my custom message');
        $actual = $error->getErrorMessage();
        $this->assertContains('my custom message', $actual);

        $actual = $statement->getErrorMessage('another message');
        $this->assertContains('another message', $actual);
    }

    public function testKnownErrorMessage()
    {
        $sql = "insert into test (id, create_date) values (10, '20-10-2010')";
        $error = $this->runStatementToFail($sql);
        $actual = $error->getErrorMessage();
        $this->assertContains('Did you try to insert a string in a date field?', $actual);
    }

    public function testErrorView()
    {
        $sql = "select * from invalid_table_name";
        $error = $this->runStatementToFail($sql);

        $actual = $error->render();
        $this->assertContains('javascript', $actual);
    }

    /**
     * @param $sql
     * @return Error
     */
    protected function runStatementToFail($sql)
    {
        $statement = new Statement($sql, $this->connection);
        $statement->execute();

        return new Error($statement);
    }
}
