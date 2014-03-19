<?php

use Oracle\Support\SimpleView;

class SimpleViewTest extends PHPUnit_Framework_TestCase
{
    protected $template;
    protected $data;

    public function setUp()
    {
        $this->template = __DIR__ . '/resources/testview.phtml';
        $this->data = array('name' => 'World');
    }

    public function tearDown()
    {
        unset($this->template);
        unset($this->data);
    }

    public function testCreation()
    {
        $view = new SimpleView($this->template, $this->data);
        $this->assertInstanceOf('\\Oracle\\Support\\SimpleView', $view);
    }

    public function testRenderWithVariable()
    {
        $view = new SimpleView($this->template, $this->data);
        $actual = $view->render();
        $this->assertEquals('Hello, World', $actual);
    }

    public function testRenderWithTooManyVariables()
    {
        $data = array(
            'name' => 'WinkBrace',
            'title' => 'Master of the Universe',
        );
        $view = new SimpleView($this->template, $data);
        $actual = $view->render();
        $this->assertEquals('Hello, WinkBrace', $actual);
    }
}
