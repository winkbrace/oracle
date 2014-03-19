<?php

use Oracle\Output\Pivoter;
use Oracle\Result\Result;

class PivoterTest extends PHPUnit_Framework_TestCase
{
    /** @var Result */
    protected $result;
    /** @var Pivoter */
    protected $pivoter;

    public function setUp()
    {
        $this->result = new Result(array(
            array('name' => 'John', 'age' => 24, 'city' => 'Amsterdam', 'household' => 3),
            array('name' => 'Piet', 'age' => 24, 'city' => 'Amsterdam', 'household' => 2),
            array('name' => 'Kees', 'age' => 30, 'city' => 'Amsterdam', 'household' => 3),
            array('name' => 'Daan', 'age' => 30, 'city' => 'Rotterdam', 'household' => 4),
            array('name' => 'Dirk', 'age' => 40, 'city' => 'Rotterdam', 'household' => 1),
            array('name' => 'Nick', 'age' => 40, 'city' => 'Rotterdam', 'household' => 1),
        ));
        $this->pivoter = new Pivoter($this->result);
    }

    public function tearDown()
    {
        unset($this->result);
        unset($this->pivoter);
    }

    public function testPivot()
    {
        // pivot by city, group by age and show me the household sum per age
        $colHeadersField = 'city';
        $rowHeaders = 'age';
        $dataField = 'household';
        $showTotal = true;

        $actual = $this->pivoter->toPivot($colHeadersField, $rowHeaders, $dataField, $showTotal);
        $expected = array(
            array('age' => '24', 'Amsterdam' => '5', 'Rotterdam' => '', 'Total' => '5'),
            array('age' => '30', 'Amsterdam' => '3', 'Rotterdam' => '4', 'Total' => '7'),
            array('age' => '40', 'Amsterdam' => '', 'Rotterdam' => '2', 'Total' => '2'),
        );
        $this->assertEquals($expected, $actual);
    }

    public function testPivotWithoutTotals()
    {
        $colHeadersField = 'city';
        $rowHeaders = 'age';
        $dataField = 'household';
        $showTotal = false;

        $actual = $this->pivoter->toPivot($colHeadersField, $rowHeaders, $dataField, $showTotal);
        $expected = array(
            array('age' => '24', 'Amsterdam' => '5', 'Rotterdam' => ''),
            array('age' => '30', 'Amsterdam' => '3', 'Rotterdam' => '4'),
            array('age' => '40', 'Amsterdam' => '', 'Rotterdam' => '2'),
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Oracle\OracleException
     */
    public function testPivotMultipleColHeaders()
    {
        $colHeadersField = 'city,age';
        $rowHeaders = 'age';
        $dataField = 'household';
        $showTotal = false;

        $this->pivoter->toPivot($colHeadersField, $rowHeaders, $dataField, $showTotal);
        $this->fail('passing list or array of multiple column header fields should throw OracleException');
    }

    public function testPivotMultipleRowHeaders()
    {
        $colHeadersField = 'city';
        $rowHeaders = 'name,age';
        $dataField = 'household';
        $showTotal = false;

        $actual = $this->pivoter->toPivot($colHeadersField, $rowHeaders, $dataField, $showTotal);
        $expected = array(
            array('name' => 'Daan', 'age' => 30, 'Amsterdam' => '', 'Rotterdam' => '4'),
            array('name' => 'Dirk', 'age' => 40, 'Amsterdam' => '', 'Rotterdam' => '1'),
            array('name' => 'John', 'age' => 24, 'Amsterdam' => '3', 'Rotterdam' => ''),
            array('name' => 'Kees', 'age' => 30, 'Amsterdam' => '3', 'Rotterdam' => ''),
            array('name' => 'Nick', 'age' => 40, 'Amsterdam' => '', 'Rotterdam' => '1'),
            array('name' => 'Piet', 'age' => 24, 'Amsterdam' => '2', 'Rotterdam' => ''),
        );
        $this->assertEquals($expected, $actual);
    }

    public function testPivotDoNotSort()
    {
        $colHeadersField = 'city';
        $rowHeaders = 'name,age';
        $dataField = 'household';
        $showTotal = false;

        $this->pivoter->setSortByFirstColumn(false);
        $actual = $this->pivoter->toPivot($colHeadersField, $rowHeaders, $dataField, $showTotal);
        $expected = array(
            array('name' => 'John', 'age' => 24, 'Amsterdam' => '3', 'Rotterdam' => ''),
            array('name' => 'Piet', 'age' => 24, 'Amsterdam' => '2', 'Rotterdam' => ''),
            array('name' => 'Kees', 'age' => 30, 'Amsterdam' => '3', 'Rotterdam' => ''),
            array('name' => 'Daan', 'age' => 30, 'Amsterdam' => '', 'Rotterdam' => '4'),
            array('name' => 'Dirk', 'age' => 40, 'Amsterdam' => '', 'Rotterdam' => '1'),
            array('name' => 'Nick', 'age' => 40, 'Amsterdam' => '', 'Rotterdam' => '1'),
        );
        $this->assertEquals($expected, $actual);
    }
}
