<?php

use Oracle\Export\FlatFileStreamer;
use Oracle\Query\Fetcher;
use Oracle\Result\Row;

class FlatFileStreamerTest extends PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface|Fetcher */
    protected $fetcher;

    public function setUp()
    {
        $this->fetcher = Mockery::mock('\\Oracle\\Query\\Fetcher');
    }

    public function tearDown()
    {
        unset($this->fetcher);
    }

    public function testCreation()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');
        $this->assertInstanceOf('\\Oracle\\Export\\FlatFileStreamer', $streamer);
        $this->assertInstanceOf('\\Oracle\\Export\\DataStreamer', $streamer);
    }

    public function testFilename()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');
        $this->assertEquals('test.csv', $streamer->getFilename());
    }

    public function testType()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test');
        $streamer->setType('psv');
        $this->assertEquals('psv', $streamer->getType());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidType()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test');
        $streamer->setType('boo');
    }

    public function testTypeByFileExtension()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test.txt');
        $this->assertEquals('txt', $streamer->getType());
    }

    public function testSeparator()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test');
        $streamer->setSeparator('foo');
        $this->assertEquals('foo', $streamer->getSeparator());
    }

    public function testSeparatorByFilename()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');
        $this->assertEquals(',', $streamer->getSeparator(), 'separator should be set on file extension');
    }

    public function testSeparatorByType()
    {
        $streamer = new FlatFileStreamer($this->fetcher, 'test');
        $streamer->setType('tsv');
        $this->assertEquals('~', $streamer->getSeparator(), 'separator should be set on type');
    }

    public function testNext()
    {
        $row1 = new Row(array('name' => 'John', 'age' => 24, 'city' => 'Amsterdam'));
        $row2 = new Row(array('name' => 'Piet', 'age' => 24, 'city' => 'Den Haag'));
        $row3 = new Row(array('name' => 'Kees', 'age' => 30, 'city' => 'Rotterdam'));
        $this->fetcher->shouldReceive('fetch')->times(4)->andReturn($row1, $row2, $row3, false);

        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');
        $first = $streamer->next();
        $second = $streamer->next();
        $third = $streamer->next();
        $fourth = $streamer->next();

        $this->assertEquals('John,24,Amsterdam'."\r\n", $first);
        $this->assertEquals('Piet,24,Den Haag'."\r\n", $second);
        $this->assertEquals('Kees,30,Rotterdam'."\r\n", $third);
        $this->assertFalse($fourth);
    }

    public function testGetHeadersLine()
    {
        $this->fetcher->shouldReceive('getColumnNames')->once()->andReturn(array('name', 'age', 'city'));
        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');

        $head = $streamer->getHeadersLine();
        $this->assertEquals('name,age,city'."\r\n", $head);
    }

    public function testSetSeparaterShouldBeDominant()
    {
        $row1 = new Row(array('name' => 'John', 'age' => 24, 'city' => 'Amsterdam'));
        $this->fetcher->shouldReceive('fetch')->once()->andReturn($row1);
        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');

        $streamer->setSeparator('foo');
        $this->assertEquals('foo', $streamer->getSeparator());

        $expected = 'Johnfoo24fooAmsterdam'."\r\n";
        $this->assertEquals($expected, $streamer->next());
    }

    public function testSetLineEnding()
    {
        $row1 = new Row(array('name' => 'John', 'age' => 24, 'city' => 'Amsterdam'));
        $this->fetcher->shouldReceive('fetch')->once()->andReturn($row1);
        $streamer = new FlatFileStreamer($this->fetcher, 'test.csv');
        $streamer->setLineEnding("\n");
        $line = $streamer->next();
        $this->assertEquals('John,24,Amsterdam'."\n", $line);
    }

}
