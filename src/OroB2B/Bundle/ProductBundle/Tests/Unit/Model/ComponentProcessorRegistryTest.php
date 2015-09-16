<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;

class ComponentProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $name = 'processor1';
        $processorOne = $this->getProcessorMock($name);

        $registry = new ComponentProcessorRegistry();

        $this->assertEmpty($registry->getProcessors());
        $this->assertNull($registry->getProcessorByName($name));

        $registry->addProcessor($processorOne);

        $this->assertCount(1, $registry->getProcessors());
        $this->assertTrue($registry->hasProcessor($name));
        $this->assertEquals($processorOne, $registry->getProcessorByName($name));
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorInterface
     */
    protected function getProcessorMock($name)
    {
        $processor = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface');
        $processor->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $processor;
    }

    public function testHasAllowedProcessor()
    {
        $processorAllowed = $this->getProcessorMock('allowed');
        $processorDisallowed = $this->getProcessorMock('disallowed');

        $registry = new ComponentProcessorRegistry();
        $registry->addProcessor($processorAllowed);
        $registry->addProcessor($processorDisallowed);

        $this->assertFalse($registry->hasAllowedProcessor());

        $processorAllowed->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->assertTrue($registry->hasAllowedProcessor());
    }
}
