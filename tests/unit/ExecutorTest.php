<?php

use Oracle\Connection;
use Oracle\Query\Executor;
use Oracle\Query\Statement;

class ExecutorTest extends PHPUnit_Framework_TestCase
{
    /** @var Connection */
    protected $connection;
    /** @var Statement */
    protected $statement;
    /** @var Executor */
    protected $executor;

    public function setUp()
    {
        $sql = "select * from test";
        $this->connection = new Connection('TEST');
        $this->statement = new Statement($sql, $this->connection);
        $this->executor = new Executor($this->statement);
    }

    public function tearDown()
    {
        unset($this->connection);
        unset($this->statement);
        unset($this->executor);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Query\\Executor', $this->executor);
    }

    public function testExecution()
    {
        $this->executor->execute();
        $this->assertTrue($this->executor->isExecuted());

        // statement has it's own (hidden) Error instance
        $this->statement->execute();
        $this->assertTrue($this->statement->isExecuted());
    }

    /**
     * test that the query will still execute when logging is enabled
     * Testing of the Logger is done elsewhere
     */
    public function testLogging()
    {
        \Oracle\Support\Config::put('logging', true);
        require_once realpath(__DIR__ . '/../../bootstrap.php'); // creates Logger and puts it in Config

        $executor = new Executor($this->statement); // logger gets attached on construction
        $executor->execute();
        $this->assertTrue($executor->isExecuted());

        \Oracle\Support\Config::put('logging', false);
    }

    /**
     * test if the correct bind variable is used in case of overlap
     * Example: you could have :reseller or :resellersoort, and only :resellersoort is in the query
     * We don't want to attempt to bind :reseller in this case
     * I copied this test from BinderTest, because I absolutely want to make sure the query executes as expected
     * @group database
     */
    public function testOverlappingBindVariableNames()
    {
        $sql = "select *
                from   test
                where  id = :identifier";
        $statement = new Statement($sql, new Connection());
        $statement->bind(array(
            ':id' => 'ACME',
            ':identifier' => 1,
        ));
        $result = $statement->execute();
        $this->assertTrue($result);
    }

    public function testCommit()
    {
        $actual = $this->executor->commit();
        $this->assertTrue($actual);
    }

    public function testRollback()
    {
        $actual = $this->executor->rollback();
        $this->assertTrue($actual);
    }

    public function testInsertAndReturnLastInsertedId()
    {
        $statement = new Statement("insert into test (value, create_date) values ('foo', sysdate)", new Connection('test'));
        $executor = new Executor($statement);
        $actual = $executor->insert('SEQ_TEST', Executor::NO_COMMIT);
        $this->assertTrue(is_numeric($actual));

        $executor->rollback();
    }

    public function testInsertMultiple()
    {
        $data = array(
            array('foo', '01-01-2014'),
            array('bar', '02-01-2014'),
            array('baz', '03-01-2014'),
        );
        $sql = "insert into test (value, create_date) values (:val, to_date(:cd, 'dd-mm-yyyy'))";
        $connection = new Connection('test');
        $statement = new Statement($sql, $connection);
        $executor = new Executor($statement);

        $binds = array(':val', ':cd');
        $sizes = array(10, 10);
        $errors = $executor->executeMultiple($binds, $sizes, $data, Executor::NO_COMMIT);
        $this->assertSame(0, $errors);

        // validate that we now have 3 extra records
        $statement = new Statement("select count(*) from test", $connection);
        $fetcher = new \Oracle\Query\Fetcher($statement);
        $count = $fetcher->fetchFirstValue();
        $this->assertEquals(7, $count);

        $executor->rollback();
    }
}
