<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculator;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Resolver\AbstractAddressResolver;
use OroB2B\Bundle\TaxBundle\Tests\ResultComparatorTrait;

abstract class AbstractAddressResolverTestCase extends \PHPUnit_Framework_TestCase
{
    use ResultComparatorTrait;

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

    /** @var AbstractAddressResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->settingsProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();

        $this->matcher = $this->getMock('OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface');
        $this->calculator = new TaxCalculator();

        $this->resolver = $this->createResolver();
    }

    /** @return AbstractAddressResolver */
    abstract protected function createResolver();

    protected function assertNothing()
    {
        $this->settingsProvider->expects($this->never())->method($this->anything());
        $this->matcher->expects($this->never())->method($this->anything());
    }

    /**
     * @param string $taxCode
     * @param string $taxRate
     * @return TaxRule
     */
    protected function getTaxRule($taxCode, $taxRate)
    {
        $taxRule = new TaxRule();
        $tax = new Tax();
        $tax
            ->setRate($taxRate)
            ->setCode($taxCode);
        $taxRule->setTax($tax);

        return $taxRule;
    }

    /**
     * @return Taxable
     */
    abstract protected function getTaxable();

    /**
     * @param Taxable $taxable
     */
    abstract protected function assertEmptyResult(Taxable $taxable);

    public function testDestinationMissing()
    {
        $taxable = $this->getTaxable();
        $taxable->setPrice('1');
        $taxable->setAmount('1');

        $this->assertNothing();

        $this->resolver->resolve($taxable);

        $this->assertEmptyResult($taxable);
    }

    public function testEmptyAmount()
    {
        $taxable = $this->getTaxable();

        $this->assertNothing();

        $this->resolver->resolve($taxable);

        $this->assertEmptyResult($taxable);
    }

    public function testEmptyRules()
    {
        $taxable = $this->getTaxable();
        $taxable->setDestination(new OrderAddress());
        $taxable->setPrice('1');
        $taxable->setAmount('1');

        $this->matcher->expects($this->once())->method('match')->willReturn([]);
        $this->resolver->resolve($taxable);

        $this->compareResult(
            new Result(
                [
                    Result::ROW => [
                        ResultElement::INCLUDING_TAX => '1',
                        ResultElement::EXCLUDING_TAX => '1',
                        ResultElement::TAX_AMOUNT => '0',
                        ResultElement::ADJUSTMENT => '0',
                    ],
                    Result::UNIT => [
                        ResultElement::INCLUDING_TAX => '1',
                        ResultElement::EXCLUDING_TAX => '1',
                        ResultElement::TAX_AMOUNT => '0',
                        ResultElement::ADJUSTMENT => '0',
                    ],
                    Result::TAXES => [],
                ]
            ),
            $taxable->getResult()
        );
    }

    /**
     * @dataProvider rulesDataProvider
     * @param string $taxableAmount
     * @param array $taxRules
     * @param Result $expectedResult
     * @param bool $startWithRowTotal
     */
    public function testRules($taxableAmount, array $taxRules, Result $expectedResult, $startWithRowTotal = false)
    {
        $taxable = $this->getTaxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination(new OrderAddress());

        $this->matcher->expects($this->once())->method('match')->willReturn($taxRules);
        $this->settingsProvider->expects($this->any())->method('isStartCalculationWithRowTotal')
            ->willReturn($startWithRowTotal);
        $this->settingsProvider->expects($this->any())->method('isStartCalculationWithUnitPrice')
            ->willReturn(!$startWithRowTotal);

        $this->resolver->resolve($taxable);

        $this->compareResult($expectedResult, $taxable->getResult());
    }

    /**
     * @return array
     */
    abstract public function rulesDataProvider();
}
