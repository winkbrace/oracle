<?php

use Oracle\Result\Row;

class RowTest extends PHPUnit_Framework_TestCase
{
    /** @var Row */
    protected $row;

    public function setUp()
    {
        $this->row = new Row();
    }

    public function tearDown()
    {
        unset($this->row);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Result\\Row', $this->row);
        $this->assertInstanceOf('\\Illuminate\\Support\\Collection', $this->row);
    }

    public function testAttributesAccessibleAsArray()
    {
        $this->row->foo = 'one';
        $this->row->bar = 'two';

        $this->assertEquals('one', $this->row['foo']);
        $this->assertEquals('two', $this->row->last());
    }

    public function testIndifferenceArrayAccess()
    {
        $this->row['foo'] = 'one';
        $this->assertEquals('one', $this->row['FOO']);
        $this->assertEquals('one', $this->row['foo']);
    }

    public function testIndifferenceObjectAccess()
    {
        $this->row->foo = 'one';
        $this->assertEquals('one', $this->row['FOO']);
        $this->assertEquals('one', $this->row['foo']);
        $this->assertEquals('one', $this->row->foo);
        $this->assertEquals('one', $this->row->FOO);
    }

    public function testUseInArrayFunction()
    {
        $this->row->foo = 'one';
        $this->row->bar = null;

        $actual = array_filter($this->row->toArray());
        $expected = array('FOO' => 'one');
        $this->assertEquals($expected, $actual);
    }

    public function testSetValue()
    {
        // in the php docs I read something about having to implement __get by reference &__get.
        // This tests asserts that that is not true.

        $class = new StdClass();
        $class->row = new Row();
        $class->row['foo'] = 'one';
        $class->row['bar'] = 'two';
        $class->row['foo'] = 'three';

        $expected = array('FOO' => 'three', 'BAR' => 'two');
        $this->assertEquals($expected, $class->row->toArray());
    }
}
