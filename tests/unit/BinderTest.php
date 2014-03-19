<?php

use Oracle\Connection;
use Oracle\Query\Binder;
use Oracle\Query\Statement;

class BinderTest extends PHPUnit_Framework_TestCase
{
    /** @var Connection */
    protected $connection;
    /** @var Statement */
    protected $statement;
    /** @var Binder */
    protected $binder;
    /** @var array */
    protected $bindVariables;

    public function setUp()
    {
        $sql = "select * from test where id = :id and create_date < to_date(:create_date, 'dd-mm-yyyy')";
        $this->connection = new Connection('test');
        $this->statement = new Statement($sql, $this->connection);
        $this->binder = new Binder($this->statement);
        $this->bindVariables = array(
            ':id' => 1,
            ':create_date' => date('d-m-Y'),
        );
    }

    public function tearDown()
    {
        unset($this->connection);
        unset($this->statement);
        unset($this->binder);
        unset($this->bindVariables);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Query\\Binder', $this->binder);
    }

    public function testBind()
    {
        $this->binder->bind($this->bindVariables);
        $this->assertEquals($this->bindVariables, $this->binder->getBindVariables());
    }

    public function testTooManyBindVariables()
    {
        $binds = $this->bindVariables + array('foo' => 'bar');
        $this->binder->bind($binds);
        $this->assertEquals($this->bindVariables, $this->binder->getBindVariables(), 'Passing too many bind variables should be silently ignored.');
    }

    public function testBindArray()
    {
        $sql = "select * from test where id in (:ids)";
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);
        $binder->bind(array(
            ':ids' => array(1, 2, 3),
        ));
        $expected = array(':ids0' => 1, ':ids1' => 2, ':ids2' => 3);
        $this->assertEquals($expected, $binder->getBindVariables());
    }

    public function testBindArrayOfOne()
    {
        $sql = "select * from test where id in (:ids)";
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);
        $binder->bind(array(
            ':ids' => array(1),
        ));
        $expected = array(':ids' => 1);
        $this->assertEquals($expected, $binder->getBindVariables());
    }

    /**
     * test if the correct bind variable is used in case of overlap
     * Example: you could have :reseller or :resellersoort, and only :resellersoort is in the query
     * We don't want to attempt to bind :reseller in this case
     */
    public function testOverlappingBindVariableNames()
    {
        $sql = "select *
                from   dm_resellers r
                join   dm_resellersoort rs on r.soortcode = rs.soortcode
                where  rs.soortcode = :resellersoort";
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);
        $binder->bind(array(
            ':reseller' => 'ACME',
            ':resellersoort' => 1,
        ));
        $this->assertEquals(':resellersoort => 1', trim($binder->toStringBindVariables()));
    }

    public function testOverlappingBindVariableNamesWhereBothExist()
    {
        $sql = "select *
                from   dm_resellers r
                join   dm_resellersoort rs on r.soortcode = rs.soortcode
                where  rs.soortcode = :resellersoort
                and    r.naam = :reseller";
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);
        $binder->bind(array(
            ':reseller' => 'ACME',
            ':resellersoort' => 1,
        ));
        $this->assertEquals(':reseller => ACME'."\n".':resellersoort => 1', trim($binder->toStringBindVariables()));
    }

    public function testSqlWithoutBindVariables()
    {
        $sql = "select *
                from   dm_resellers r
                join   dm_resellersoort rs on r.soortcode = rs.soortcode";
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);
        $binder->bind(array(
            ':reseller' => 'ACME',
            ':resellersoort' => 1,
        ));
        $this->assertEmpty($binder->getBindVariables());
    }

    public function testNonWordCharactersImmediatelyFollowingTheBindVariable()
    {
        $sql = "select *
                from   test
                where  id > :id
                and    :nr=3
                and    2=:two";  // no nextChar
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);
        $binder->bind(array(
            ':id' => 2,
            ':nr' => 3,
            ':two' => 2,
        ));
        $this->assertEquals(
            ':id => 2'."\n".':nr => 3'."\n".':two => 2',
            trim($binder->toStringBindVariables())
        );
    }

    public function testOutParameters()
    {
        // this procedure will assign 1 to :one and 2 to :two
        $sql = "begin test_out_params(:one, :two); end;";
        $statement = new Statement($sql, $this->connection);
        $binder = new Binder($statement);

        $binder->bindOutParameter(':one', 'first', 10);
        $binder->bindOutParameter(':two', 'second', 10);

        $this->assertArrayHasKey('first', $binder->getOutParameters());

        $executor = new \Oracle\Query\Executor($statement);
        $executor->execute();

        $this->assertEquals('2', $binder->getOutParameter('second'));
    }
}
