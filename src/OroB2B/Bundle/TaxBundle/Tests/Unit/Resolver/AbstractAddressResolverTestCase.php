<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Resolver\AbstractAddressResolver;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

abstract class AbstractAddressResolverTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    /**
     * @var MatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matcher;

    /**
     * @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $calculator;

    /**
     * @var TaxRoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rounding;

    /** @var AbstractAddressResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->settingsProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();

        $this->matcher = $this->getMock('OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface');
        $this->calculator = $this->getMock('OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');
        $this->rounding = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService')
            ->disableOriginalConstructor()->getMock();

        $this->resolver = $this->createResolver();
    }

    /** @return AbstractAddressResolver */
    abstract protected function createResolver();

    protected function assertNothing()
    {
        $this->settingsProvider->expects($this->never())->method($this->anything());
        $this->matcher->expects($this->never())->method($this->anything());
        $this->calculator->expects($this->never())->method($this->anything());
        $this->rounding->expects($this->never())->method($this->anything());
    }

    protected function assertRoundServiceCalled()
    {
        $this->calculator->expects($this->atLeastOnce())->method('calculate')->willReturnCallback(
            function ($taxableAmount, $taxRate) {
                $inclTax = round($taxableAmount * (1 + $taxRate), 2);
                $exclTax = round($taxableAmount, 2);

                return ResultElement::create($inclTax, $exclTax, round($inclTax - $exclTax, 2), 0);
            }
        );
    }
}
