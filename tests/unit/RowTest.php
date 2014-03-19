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
}
