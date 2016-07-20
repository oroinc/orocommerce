<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffMapperRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutStateDiffMapperRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new CheckoutStateDiffMapperRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testEmptyGetMappers()
    {
        $mappers = $this->registry->getMappers();
        $this->assertInternalType('array', $mappers);
        $this->assertEmpty($mappers);
    }

    public function testAddMapper()
    {
        $mapper = $this->getMapper();
        $mapper->expects($this->once())
            ->method('getName')
            ->willReturn('test_name');

        $this->registry->addMapper($mapper);

        $mappers = $this->registry->getMappers();

        $this->assertCount(1, $mappers);
        $this->assertContains($mapper, $mappers);

        $mapper2 = $this->getMapper();
        $mapper2->expects($this->once())
            ->method('getName')
            ->willReturn('test_other_name');

        $this->registry->addMapper($mapper2);

        $mappers = $this->registry->getMappers();

        $this->assertCount(2, $mappers);
        $this->assertContains($mapper, $mappers);
    }

    public function testRegistry()
    {
        $mapper = $this->getMapper();
        $mapper->expects($this->once())
            ->method('getName')
            ->willReturn('test_name');

        $this->registry->addMapper($mapper);
        $this->assertEquals($mapper, $this->registry->getMapper('test_name'));
        $this->assertEquals(['test_name' => $mapper], $this->registry->getMappers());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Mapper "wrong_name" is missing. Registered mappers are ""
     */
    public function testRegistryException()
    {
        $this->registry->getMapper('wrong_name');
    }

    /**
     * @return CheckoutStateDiffMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMapper()
    {
        return $this->getMock('OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface');
    }
}
