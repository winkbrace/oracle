<?php

use Oracle\Dump\Debug;

class DebugTest extends PHPUnit_Framework_TestCase
{
    /** @var Oracle\Query\Statement|Mockery\MockInterface */
    protected $statement;
    /** @var Debug */
    protected $debug;

    public function setUp()
    {
        $this->statement = Mockery::mock('\\Oracle\\Query\\Statement');
        $this->statement->shouldReceive('getSql')->once()->andReturn("select * from test where id = :id");
        $this->statement->shouldReceive('getSchema')->once()->andReturn('TEST');
        $this->statement->shouldReceive('toStringBindVariables')->once()->andReturn(':id => 2');

        $this->debug = new Debug($this->statement);
    }

    public function tearDown()
    {
        unset($this->statement);
        unset($this->debug);
    }

    public function testCreation()
    {
        $this->assertInstanceOf('\\Oracle\\Dump\\Debug', $this->debug);
    }

    public function testRender()
    {
        $render = $this->debug->render();
        $this->assertContains('select * from', $render);
        $this->assertContains(':id => 2', $render);
        $this->assertContains('schema: TEST', $render);
    }
}
