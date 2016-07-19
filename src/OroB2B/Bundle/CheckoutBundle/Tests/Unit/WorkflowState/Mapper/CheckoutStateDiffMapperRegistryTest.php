<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffMapperRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutStateDiffMapperRegistry */
    protected $registry;

    /** @var CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mapper;

    protected function setUp()
    {
        $this->registry = new CheckoutStateDiffMapperRegistry();

        $this->mapper = $this->getMockBuilder(
            'OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface'
        )
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->registry, $this->mapper);
    }

    public function testGetMappers()
    {
        $mappers = $this->registry->getMappers();
        $this->assertInternalType('array', $mappers);
        $this->assertEmpty($mappers);
    }

    public function testAddMapper()
    {
        $this->registry->addMapper($this->mapper);
        $this->assertContains($this->mapper, $this->registry->getMappers());
    }

    public function testRegistry()
    {
        $this->mapper->expects($this->any())
            ->method('getName')
            ->willReturn('test_name');

        $this->registry->addMapper($this->mapper);
        $this->assertEquals($this->mapper, $this->registry->getMapper('test_name'));
        $this->assertEquals(['test_name' => $this->mapper], $this->registry->getMappers());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Mapper "wrong_name" is missing. Registered mappers are ""
     */
    public function testRegistryException()
    {
        $this->registry->getMapper('wrong_name');
    }
}
