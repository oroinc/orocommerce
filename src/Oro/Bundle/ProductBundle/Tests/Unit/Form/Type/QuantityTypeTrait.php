<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;

/**
 * @method \PHPUnit\Framework\MockObject\MockBuilder getMock($className)
 * @method \PHPUnit\Framework\MockObject\Matcher\AnyInvokedCount any()
 */
trait QuantityTypeTrait
{
    /**
     * @var string
     */
    public static $name = QuantityType::NAME;

    /**
     * @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $roundingService;

    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $formatterService;

    /**
     * @return RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getRoundingService()
    {
        if (!$this->roundingService) {
            $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        }

        return $this->roundingService;
    }
    /**
     * @return NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getFormatterService()
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

    /**
     * @return QuantityType|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getQuantityType()
    {
        $type = new QuantityType(
            $this->getRoundingService(),
            'Oro\Bundle\ProductBundle\Entity\Product'
        );
        $type->setNumberFormatter($this->getFormatterService());

        return $type;
    }
}
