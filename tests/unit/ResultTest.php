<?php

use Oracle\Result\Result;
use Oracle\Result\Row;

class ResultTest extends PHPUnit_Framework_TestCase
{
    /** @var Result */
    protected $resultset;  // $result is phpunit property

    public function setUp()
    {
        // create result set of 2 Rows
        $this->resultset = new Result();
        $this->resultset->push(new Row(array('ONE' => 'foo', 'TWO' => 'bar', 'THREE' => 'baz')));
        $this->resultset->push(new Row(array('ONE' => 'moo', 'TWO' => 'mar', 'THREE' => 'maz')));
    }

    public function tearDown()
    {
        unset($this->resultset);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Result\\Result', $this->resultset);
        $this->assertInstanceOf('\\Illuminate\\Support\\Collection', $this->resultset);
    }

    public function testArrayAccess()
    {
        foreach ($this->resultset as $row)
        {
            $this->assertInstanceOf('\\Oracle\\Result\\Row', $row);
        }
    }

    public function testRowAccess()
    {
        $row = $this->resultset->shift();
        $this->assertEquals('bar', $row->two);
    }
}
