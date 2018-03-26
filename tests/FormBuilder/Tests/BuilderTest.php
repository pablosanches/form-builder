<?php

namespace FormBuilder\Tests;

use FormBuilder\Builder;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {}

    public function testInitialize()
    {
        $builder = new Builder();
        $this->assertInstanceOf('FormBuilder\Builder', $builder);

        return $builder;
    }
}