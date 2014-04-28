<?php

use Oracle\Support\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $credentials = Config::get('credentials');
        $this->assertTrue(is_array($credentials));
        $this->assertNotEmpty($credentials);
        $this->assertArrayHasKey('MARPRD', $credentials);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAskForNotExistingKey()
    {
        $foo = Config::get('foo');
        $this->fail('Should throw InvalidArgumentException');
    }

    public function testAll()
    {
        $config = Config::all();
        $this->assertTrue(is_array($config));
        $this->assertNotEmpty($config);
        $this->assertArrayHasKey('credentials', $config);
    }

    public function testPut()
    {
        $config = Config::all();
        $this->assertArrayNotHasKey('foo', $config);
        Config::put('foo', 'bar');
        $config = Config::all();
        $this->assertArrayHasKey('foo', $config);
        $this->assertEquals('bar', Config::get('foo'));
    }
}
