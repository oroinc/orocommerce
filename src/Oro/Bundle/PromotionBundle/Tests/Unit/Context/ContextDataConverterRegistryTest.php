<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterRegistry;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class ContextDataConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testSupports()
    {
        $entity = new \stdClass();
        $registry = new ContextDataConverterRegistry([]);
        $this->assertFalse($registry->supports($entity));

        /** @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(ContextDataConverterInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(true);

        $registry = new ContextDataConverterRegistry([$converter]);
        $this->assertTrue($registry->supports($entity));
    }

    public function testConvert()
    {
        $entity = new \stdClass();
        $context = [];

        /** @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(ContextDataConverterInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(true);
        $converter->expects($this->once())
            ->method('getContextData')
            ->with($entity)
            ->willReturn($context);

        $registry = new ContextDataConverterRegistry([$converter]);
        $this->assertSame($context, $registry->getContextData($entity));
    }

    public function testConvertWithNotSupportedEntity()
    {
        $entity = new \stdClass();

        /** @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(ContextDataConverterInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(false);
        $converter->expects($this->never())
            ->method('getContextData')
            ->with($entity);

        $registry = new ContextDataConverterRegistry([$converter]);

        $this->expectException(UnsupportedSourceEntityException::class);
        $registry->getContextData($entity);
    }
}
