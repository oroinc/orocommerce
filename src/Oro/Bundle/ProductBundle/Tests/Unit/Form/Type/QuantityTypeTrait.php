<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;

trait QuantityTypeTrait
{
    private ?NumberFormatter $formatterService = null;

    private function getFormatterService(): NumberFormatter
    {
        if (!$this->formatterService) {
            $this->formatterService = $this->createMock(NumberFormatter::class);
            $this->formatterService->expects(self::any())
                ->method('parseFormattedDecimal')
                ->willReturnCallback(function ($value) {
                    return (float)$value;
                });
            $this->formatterService->expects(self::any())
                ->method('formatDecimal')
                ->willReturnArgument(0);
        }

        return $this->formatterService;
    }

    private function getQuantityType(): QuantityType
    {
        return new QuantityType($this->getFormatterService(), Product::class);
    }
}
