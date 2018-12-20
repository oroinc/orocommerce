<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
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
     * @return RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getRoundingService()
    {
        if (!$this->roundingService) {
            $this->roundingService = $this->createMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
        }

        return $this->roundingService;
    }

    /**
     * @return RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getQuantityType()
    {
        return new QuantityType($this->getRoundingService(), 'Oro\Bundle\ProductBundle\Entity\Product');
    }

    public function addRoundingServiceExpect()
    {
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision) {
                    return round($value, $precision);
                }
            );
    }
}
