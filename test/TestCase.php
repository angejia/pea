<?php namespace Lvht\Pea;

use Illuminate\Container\Container;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $app;

    public function setUp()
    {
        $container = new Container;
        Container::setInstance($container);
        $this->app = Container::getInstance();
    }
}
