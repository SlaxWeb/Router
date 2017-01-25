<?php
namespace SlaxWeb\Router\Tests\Unit;

class MockController
{
    protected $tester = null;

    public function __construct($tester)
    {
        $this->tester = $tester;
    }

    public function customDefault()
    {
        $this->tester->call("customDefault");
    }

    public function uriMethod()
    {
        $this->tester->call("uriMethod");
    }

    public function methodWithParams($param)
    {
        $this->tester->call("methodWithParams", $param);
    }
}
