<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;

trait QuantityTypeTrait
{
    /** @var string */
    public static $name = QuantityType::NAME;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $formatterService;

    /**
     * @return NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFormatterService()
    {
        if (!$this->formatterService) {
            $this->formatterService = $this->createMock(NumberFormatter::class);
            $this->formatterService->expects($this->any())
                ->method('parseFormattedDecimal')
                ->willReturnCallback(function ($value) {
                    return (float)$value;
                });
            $this->formatterService->expects($this->any())
                ->method('formatDecimal')
                ->willReturnArgument(0);
        }

        return $this->formatterService;
    }

    protected function getQuantityType(): QuantityType
    {
        return new QuantityType($this->getFormatterService(), Product::class);
    }
}
