<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperRegistry;

class CheckoutStateDiffMapperRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyGetMappers()
    {
        $registry = new CheckoutStateDiffMapperRegistry([]);
        $this->assertSame([], $registry->getMappers());
    }

    public function testRegistry()
    {
        $mapper = $this->createMock(CheckoutStateDiffMapperInterface::class);
        $mapper->expects($this->once())
            ->method('getName')
            ->willReturn('test_name');

        $registry = new CheckoutStateDiffMapperRegistry([$mapper]);
        $this->assertSame($mapper, $registry->getMapper('test_name'));
        $this->assertEquals(['test_name' => $mapper], $registry->getMappers());
    }

    public function testRegistryException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mapper "wrong_name" is missing. Registered mappers: test1, test2.');

        $mapper1 = $this->createMock(CheckoutStateDiffMapperInterface::class);
        $mapper1->expects($this->once())
            ->method('getName')
            ->willReturn('test1');
        $mapper2 = $this->createMock(CheckoutStateDiffMapperInterface::class);
        $mapper2->expects($this->once())
            ->method('getName')
            ->willReturn('test2');

        $registry = new CheckoutStateDiffMapperRegistry([$mapper1, $mapper2]);
        $registry->getMapper('wrong_name');
    }

    public function testReset()
    {
        $mapper = $this->createMock(CheckoutStateDiffMapperInterface::class);
        $mapper->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('test_name');

        $registry = new CheckoutStateDiffMapperRegistry([$mapper]);
        $this->assertSame($mapper, $registry->getMapper('test_name'));
        $registry->reset();
        $this->assertSame($mapper, $registry->getMapper('test_name'));
    }
}
