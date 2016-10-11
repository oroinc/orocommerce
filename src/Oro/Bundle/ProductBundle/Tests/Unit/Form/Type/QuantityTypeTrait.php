<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;

/**
 * @method \PHPUnit_Framework_MockObject_MockBuilder getMock($className)
 * @method \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount any()
 */
trait QuantityTypeTrait
{
    /**
     * @var string
     */
    public static $name = QuantityType::NAME;

    /**
     * @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * @return RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getRoundingService()
    {
        if (!$this->roundingService) {
            $this->roundingService = $this->getMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
        }

        return $this->roundingService;
    }

    /**
     * @return RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
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
