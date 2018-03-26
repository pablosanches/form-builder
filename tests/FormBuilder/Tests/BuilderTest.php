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

    /**
     * @depends testInitialize
     * @return FormBuilder\Builder $builder
     */
    public function testAddInput($builder)
    {
        $builder->addInput('Nome', [
            'request_populate' => false
        ], 'contact_name');

        $input = $builder->getInput('contact_name');
        $this->assertEquals('contact_name', $input['name']);
    }

    /**
     * @depends testInitialize
     * @return FormBuilder\Builder $builder
     */
    public function testBuild($builder)
    {
        $output = $builder->buildForm();
        $this->assertNotEmpty($output);
    }    
}