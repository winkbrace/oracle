<?php

use Oracle\Connection;
use Oracle\Query\Fetcher;
use Oracle\Query\Statement;

class FetcherTest extends PHPUnit_Framework_TestCase
{
    /** @var Statement */
    protected $statement;
    /** @var Fetcher */
    protected $fetcher;

    public function setUp()
    {
        $this->statement = new Statement("select * from test order by id", new Connection('test'));
        $this->fetcher = new Fetcher($this->statement);
    }

    public function tearDown()
    {
        unset($this->statement);
        unset($this->fetcher);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Query\\Fetcher', $this->fetcher);
    }

    public function testFetchResult()
    {
        $result = $this->fetcher->fetchAll();
        $this->assertGreaterThan(0, $result->count());
    }

    public function testFetchRows()
    {
        while ($row = $this->fetcher->fetch())
        {
            $this->assertInstanceOf('\\Oracle\\Result\\Row', $row);
        }
    }

    public function testDefaultDateFormat()
    {
        $result = $this->fetcher->fetchAll();

        $this->assertRegExp('/^\d{2}-\d{2}-\d{4}$/', $result->first()->create_date);
        $this->assertRegExp('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/', $result->last()->create_date);
    }

    public function testAlternativeDateFormat()
    {
        $this->fetcher->setDateFormat('Ymd');
        $this->assertEquals('Ymd', $this->fetcher->getDateFormat());

        $row = $this->fetcher->fetch();
        $this->assertRegExp('/^20\d{6}$/', $row['create_date']);
    }

    public function testColumnNames()
    {
        $actual = $this->fetcher->getColumnNames();
        $expected = array('ID', 'VALUE', 'CREATE_DATE');
        $this->assertEquals($expected, $actual);
    }

    public function testFetchNum()
    {
        $row = $this->fetcher->fetch(Fetcher::FETCH_NUM);
        $this->assertArrayHasKey('0', $row);
        $this->assertArrayHasKey('1', $row);
        $this->assertArrayHasKey('2', $row);
    }

    public function testFetchBoth()
    {
        $row = $this->fetcher->fetch(Fetcher::FETCH_BOTH);
        $this->assertArrayHasKey('0', $row);
        $this->assertArrayHasKey('ID', $row);
    }

    public function testFetchUsingOciConstant()
    {
        $row = $this->fetcher->fetch(OCI_BOTH);
        $this->assertArrayHasKey('0', $row);
        $this->assertArrayHasKey('ID', $row);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidFetchType()
    {
        $this->fetcher->fetch('ERROR');
        $this->fail('passing invalid fetch type should throw InvalidArgumentException');
    }

    public function testAllFieldsAvailable()
    {
        $result = $this->fetcher->fetchAll();
        $row = $result->last(); // this row has NULL in the value field
        $this->assertArrayHasKey('VALUE', $row);
    }

    public function testFetchLobs()
    {
        $statement = new Statement("select big_value from test_clobs", new Connection('test'));
        $fetcher = new Fetcher($statement);
        $row = $fetcher->fetch();
        $this->assertArrayHasKey('BIG_VALUE', $row);
        $this->assertTrue(is_string($row['BIG_VALUE']));
    }

    public function testHasColumn()
    {
        $this->assertTrue($this->fetcher->hasColumn('ID'));
    }

    public function testGetColumnType()
    {
        $this->assertEquals('DATE', $this->fetcher->getColumnType('CREATE_DATE'));
        $this->assertEquals('NUMBER', $this->fetcher->getColumnType(0));
    }

    public function testGetNumRowsBeforeFetching()
    {
        $num = $this->fetcher->getNumRows();
        $this->assertEquals(4, $num);
    }

    public function testGetNumRowsAfterFetching()
    {
        $this->fetcher->fetchAll();
        $this->assertEquals(4, $this->fetcher->getNumRows());
    }

    public function testGetNumRowsOfUpdate()
    {
        $statement = new Statement("update test set value = value where id in (2,4)", new Connection('test'));
        $fetcher = new Fetcher($statement);
        $this->assertEquals(2, $fetcher->getNumRows());
    }

    public function testFetchFirstValue()
    {
        $id = $this->fetcher->fetchFirstValue();
        $this->assertEquals('1', $id);
        // should not change when called 2nd time
        $id = $this->fetcher->fetchFirstValue();
        $this->assertEquals('1', $id);
    }

    public function testFetchFirstValueByColumn()
    {
        $value = $this->fetcher->fetchFirstValue('VALUE');
        $this->assertEquals('hello, world', $value);
    }

    public function testFetchArray()
    {
        $actual = $this->fetcher->fetchArray('value', 'id');
        $this->assertArrayHasKey('hello, back', $actual);
        $this->assertEquals('1', $actual['hello, world']);
    }

    public function testFetchArrayNumeric()
    {
        $actual = $this->fetcher->fetchArray();
        $this->assertArrayHasKey('4', $actual);
        $this->assertEquals('hello, world', $actual['1']);
    }

    public function testFetchColumn()
    {
        $actual = $this->fetcher->fetchColumn('ID');
        $expected = array(1, 2, 3, 4);
        $this->assertEquals($expected, $actual);
    }

    public function testFetchColumnByLowercase()
    {
        $actual = $this->fetcher->fetchColumn('id');
        $expected = array(1, 2, 3, 4);
        $this->assertEquals($expected, $actual);
    }

    public function testFetchColumnWithoutArgument()
    {
        $actual = $this->fetcher->fetchColumn();
        $expected = array(1, 2, 3, 4);
        $this->assertEquals($expected, $actual);
    }

    public function testFetchTwoColumns()
    {
        $values = $this->fetcher->fetchColumn('value');
        $ids = $this->fetcher->fetchColumn('id');
        $this->assertCount(4, $values);
        $this->assertCount(4, $ids);
    }
}
