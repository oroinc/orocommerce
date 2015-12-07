<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

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
            $this->roundingService = $this->getMock('OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface');
        }

        return $this->roundingService;
    }

    /**
     * @return RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getQuantityType()
    {
        return new QuantityType($this->getRoundingService(), 'OroB2B\Bundle\ProductBundle\Entity\Product');
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
