<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigNumber;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculator;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\AbstractResult;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Resolver\AbstractAddressResolver;

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
     * @param BigNumber[]|AbstractResult $resultElement
     * @return array
     */
    protected function extractScalarValues($resultElement)
    {
        $numberCallback = function ($number) {
            if ($number instanceof BigNumber) {
                return (string)$number;
            }

            return $number;
        };

        if ($resultElement instanceof AbstractResult) {
            $resultElement = $resultElement->getArrayCopy();
        } else {
            return array_map(
                function (AbstractResult $result) use ($numberCallback) {
                    return array_map($numberCallback, $result->getArrayCopy());
                },
                $resultElement
            );
        }

        return array_map($numberCallback, $resultElement);
    }

    /**
     * @param Result $expected
     * @param Result $actual
     */
    protected function compareResult(Result $expected, Result $actual)
    {
        foreach ($expected as $key => $expectedValue) {
            $this->assertTrue($actual->offsetExists($key));
            $actualValue = $actual->offsetGet($key);

            $this->assertEquals(
                $this->extractScalarValues($expectedValue),
                $this->extractScalarValues($actualValue)
            );
        }
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
     * @param ResolveTaxEvent $event
     */
    abstract protected function assertEmptyResult(ResolveTaxEvent $event);


    public function testDestinationMissing()
    {
        $event = new ResolveTaxEvent($this->getTaxable(), new Result());

        $this->assertNothing();

        $this->resolver->resolve($event);

        $this->assertEmptyResult($event);
    }

    public function testEmptyRules()
    {
        $taxable = $this->getTaxable();
        $taxable->setDestination(new OrderAddress());
        $taxable->setRawPrice(Price::create(1, 'USD'));
        $taxable->setRawAmount('1');
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn([]);
        $this->resolver->resolve($event);

        $this->assertEquals(
            [
                ResultElement::INCLUDING_TAX => '1',
                ResultElement::EXCLUDING_TAX => '1',
                ResultElement::TAX_AMOUNT => '0',
                ResultElement::ADJUSTMENT => '0',
            ],
            $this->extractScalarValues($event->getResult()->getUnit())
        );
        $this->assertEquals(
            [
                ResultElement::INCLUDING_TAX => '1',
                ResultElement::EXCLUDING_TAX => '1',
                ResultElement::TAX_AMOUNT => '0',
                ResultElement::ADJUSTMENT => '0',
            ],
            $this->extractScalarValues($event->getResult()->getRow())
        );
        $this->assertEquals([], $event->getResult()->getTaxes());
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
        $taxable->setRawPrice(Price::create($taxableAmount, 'USD'));
        $taxable->setRawQuantity(3);
        $taxable->setRawAmount($taxableAmount);
        $taxable->setDestination(new OrderAddress());
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn($taxRules);
        $this->settingsProvider->expects($this->any())->method('isStartCalculationWithRowTotal')
            ->willReturn($startWithRowTotal);

        $this->resolver->resolve($event);

        $this->compareResult($expectedResult, $event->getResult());
    }

    /**
     * @return array
     */
    abstract public function rulesDataProvider();
}
