<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

/**
 * @method \PHPUnit_Framework_MockObject_MockBuilder getMockBuilder($className)
 * @method \PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce atLeastOnce()
 */
trait QuantityTypeTrait
{
    /**
     * @var string
     */
    public static $name = QuantityType::NAME;

    /**
     * @var RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * @return RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getRoundingService()
    {
        return $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getQuantityType()
    {
        return new QuantityType($this->getRoundingService(), 'OroB2B\Bundle\ProductBundle\Entity\Product');
    }

    public function addRoundingServiceExpect()
    {
        $this->roundingService->expects($this->atLeastOnce())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision) {
                    return round($value, $precision);
                }
            );
    }
}
