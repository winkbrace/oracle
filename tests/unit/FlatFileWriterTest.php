<?php

use Mockery\MockInterface;
use Oracle\Export\FlatFileStreamer;
use Oracle\Export\FlatFileWriter;

class FlatFileWriterTest extends PHPUnit_Framework_TestCase
{
    /** @var FlatFileStreamer|MockInterface */
    protected $streamer;
    /** @var FlatFileWriter */
    protected $writer;
    /** @var string */
    protected $testfile;

    public function setUp()
    {
        $this->testfile = __DIR__.'/resources/FileWriterTest.csv';

        $this->streamer = Mockery::mock('\\Oracle\\Export\\FlatFileStreamer');
        $this->streamer->shouldReceive('getFilename')->once()->andReturn($this->testfile);
        $this->streamer->shouldReceive('getType')->once()->andReturn('csv');
        $this->streamer->shouldReceive('getHeadersLine')->once()->andReturn("name,age,city\r\n");
        $this->streamer->shouldReceive('next')->times(4)->andReturn(
            "John,24,Amsterdam\r\n",
            "Piet,24,Den Haag\r\n",
            "Kees,30,Rotterdam\r\n",
            false
        );

        $this->writer = new FlatFileWriter($this->streamer);
    }

    public function tearDown()
    {
        unset($this->streamer);
        unset($this->writer);
        file_put_contents($this->testfile, '');
    }

    /**
     * @return array
     */
    protected function getFileLines()
    {
        return file($this->testfile);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Export\\FlatFileWriter', $this->writer);
    }

    public function testWrite()
    {
        $this->writer->write();
        $lines = file($this->testfile);
        $this->assertCount(4, $lines);
    }

    public function testWriteMode()
    {
        $this->writer->write();

        $this->assertCount(4, $this->getFileLines());

        $this->setUp();
        $this->writer->setFileWriteMode(FlatFileWriter::FILE_APPEND);
        $this->writer->write();

        $this->assertCount(8, $this->getFileLines());

        $this->setUp();
        $this->writer->setFileWriteMode(FlatFileWriter::FILE_OVERWRITE);
        $this->writer->write();

        $this->assertCount(4, $this->getFileLines());

        // the default should be to overwrite
        $this->setUp();
        $this->writer->write();

        $this->assertCount(4, $this->getFileLines());
    }

    public function testHeaders()
    {
        $this->writer->write();
        $head = array_shift($this->getFileLines());
        $this->assertEquals("name,age,city\r\n", $head);
    }

    public function testNoHeaders()
    {
        $this->writer->setShowHeaders(false);
        $this->writer->write();
        $head = current($this->getFileLines());
        $this->assertNotEquals("name,age,city\r\n", $head);
    }

    public function testRowsPerFile()
    {
        $this->writer->setLinesPerFile(2);
        $this->writer->write();

        $file1 = __DIR__.'/resources/FileWriterTest_01.csv';
        $file2 = __DIR__.'/resources/FileWriterTest_02.csv';
        $this->assertTrue(file_exists($file1));
        $this->assertTrue(file_exists($file2));
        $this->assertCount(3, file($file1), 'First file should contain a header and 2 records.');
        $this->assertCount(2, file($file2), 'Second file should contain a header and 1 record.');
    }
}
