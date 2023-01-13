<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;

class ComponentProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testRegistry()
    {
        $name = 'processor1';
        $processorOne = $this->getProcessor($name);

        $registry = new ComponentProcessorRegistry();

        $this->assertEmpty($registry->getProcessors());
        $this->assertNull($registry->getProcessorByName($name));

        $registry->addProcessor($processorOne);

        $this->assertCount(1, $registry->getProcessors());
        $this->assertTrue($registry->hasProcessor($name));
        $this->assertEquals($processorOne, $registry->getProcessorByName($name));
    }

    private function getProcessor(string $name): ComponentProcessorInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $processor = $this->createMock(ComponentProcessorInterface::class);
        $processor->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $processor;
    }

    public function testHasAllowedProcessor()
    {
        $processorAllowed = $this->getProcessor('allowed');
        $processorDisallowed = $this->getProcessor('disallowed');

        $registry = new ComponentProcessorRegistry();
        $registry->addProcessor($processorAllowed);
        $registry->addProcessor($processorDisallowed);

        $this->assertFalse($registry->hasAllowedProcessor());

        $processorAllowed->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);
        $this->assertTrue($registry->hasAllowedProcessor());
    }
}
