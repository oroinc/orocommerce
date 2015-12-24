<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\DBAL\Types\MoneyType;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class PriceTypeGenerator extends \PHPUnit_Framework_TestCase
{
    /**
     * @return PriceType
     */
    public static function createPriceType()
    {
        $priceType = new PriceType(self::getPriceRoundingService());
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        return $priceType;
    }

    /**
     * @return RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected static function getPriceRoundingService()
    {
        $generator = new \PHPUnit_Framework_MockObject_Generator();
        $roundingService = $generator
            ->getMock('OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService', [], [], '', false);

        $roundingService->expects(new \PHPUnit_Framework_MockObject_Matcher_InvokedCount(1))
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $roundingService->expects(new \PHPUnit_Framework_MockObject_Matcher_InvokedCount(1))
            ->method('getPrecision')
            ->willReturn(MoneyType::TYPE_SCALE);

        return $roundingService;
    }
}
