<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;

trait QuantityTypeTrait
{
    private function getQuantityType(): QuantityType
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter->expects(self::any())
            ->method('parseFormattedDecimal')
            ->willReturnCallback(function ($value) {
                return (float)$value;
            });
        $numberFormatter->expects(self::any())
            ->method('formatDecimal')
            ->willReturnArgument(0);

        return new QuantityType($numberFormatter, Product::class);
    }
}
