<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterRegistry;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class DiscountContextConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testSupports()
    {
        $entity = new \stdClass();
        $registry = new DiscountContextConverterRegistry();
        $this->assertFalse($registry->supports($entity));

        /** @var DiscountContextConverterInterface|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(DiscountContextConverterInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(true);

        $registry->registerConverter($converter);
        $this->assertTrue($registry->supports($entity));
    }

    public function testConvert()
    {
        $entity = new \stdClass();
        $registry = new DiscountContextConverterRegistry();
        $discountContext = new DiscountContext();

        /** @var DiscountContextConverterInterface|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(DiscountContextConverterInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(true);
        $converter->expects($this->once())
            ->method('convert')
            ->with($entity)
            ->willReturn($discountContext);

        $registry->registerConverter($converter);
        $this->assertSame($discountContext, $registry->convert($entity));
    }

    public function testConvertWithNotSupportedEntity()
    {
        $entity = new \stdClass();
        $registry = new DiscountContextConverterRegistry();

        /** @var DiscountContextConverterInterface|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(DiscountContextConverterInterface::class);
        $converter->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(false);
        $converter->expects($this->never())
            ->method('convert')
            ->with($entity);

        $registry->registerConverter($converter);

        $this->expectException(UnsupportedSourceEntityException::class);
        $registry->convert($entity);
    }
}
