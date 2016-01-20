<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

abstract class AbstractTaxCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxCalculatorInterface */
    protected $calculator;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxRoundingService $roundingService */
        $roundingService = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService
            ->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision, $roundType) {
                    return (string)round(
                        $value,
                        $precision ?: TaxRoundingService::TAX_PRECISION,
                        $roundType === TaxRoundingService::HALF_DOWN ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP
                    );
                }
            );

        $this->calculator = $this->getCalculator($roundingService);
    }

    /**
     * @param RoundingServiceInterface $roundingService
     * @return TaxCalculatorInterface
     */
    abstract protected function getCalculator(RoundingServiceInterface $roundingService);
}
