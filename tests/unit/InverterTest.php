<?php

use Oracle\Output\Inverter;
use Oracle\Result\Result;

class InverterTest extends PHPUnit_Framework_TestCase
{
    /** @var Result */
    protected $result;
    /** @var Inverter */
    protected $inverter;

    public function setUp()
    {
        $this->result = new Result(array(
            array('one' => 1, 'two' => 2, 'three' => 3),
            array('one' => 11, 'two' => 12, 'three' => 13),
        ));
        $this->inverter = new Inverter($this->result);
    }

    public function tearDown()
    {
        unset($this->result);
        unset($this->inverter);
    }

    public function testInvert()
    {
        $actual = $this->inverter->invert();
        $expected = array(
            array('Field' => 'one', 'Record1' => 1, 'Record2' => 11),
            array('Field' => 'two', 'Record1' => 2, 'Record2' => 12),
            array('Field' => 'three', 'Record1' => 3, 'Record2' => 13),
        );
        $this->assertEquals($expected, $actual);
    }
}
