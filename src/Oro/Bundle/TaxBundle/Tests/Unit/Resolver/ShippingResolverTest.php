<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\ShippingResolver;

class ShippingResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingResolver
     */
    protected $resolver;

    /**
     * @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $excTaxCalculator;

    /**
     * @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $incTaxCalculator;

    /**
     * @var MatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $matcher;

    /**
     * @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxationSettingsProvider;

    protected function setUp(): void
    {
        $this->excTaxCalculator = $this->getMockBuilder(TaxCalculatorInterface::class)->getMock();
        $this->incTaxCalculator = $this->getMockBuilder(TaxCalculatorInterface::class)->getMock();
        $this->matcher = $this->getMockBuilder(MatcherInterface::class)
            ->getMock();
        $this->taxationSettingsProvider = $this->getMockBuilder(TaxationSettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new ShippingResolver(
            $this->incTaxCalculator,
            $this->excTaxCalculator,
            $this->matcher,
            $this->taxationSettingsProvider
        );
    }

    public function testTaxableWithoutItems()
    {
        $taxable = new Taxable();

        $this->matcher->expects($this->never())
            ->method('match');
        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTax');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableResultLocked()
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->addItem($item);
        $taxable->getResult()->lockResult();

        $this->matcher->expects($this->never())
            ->method('match');
        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTax');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testShippingRatesIncludeTaxes()
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('10');
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(true);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('getShippingTaxCodes')
            ->willReturn(['PRODUCT_TAX_CODE']);

        $taxCodes = new TaxCodes([
            new TaxCode('PRODUCT_TAX_CODE', TaxCodeInterface::TYPE_PRODUCT),
            new TaxCode('ACCOUNT_TAX_CODE', TaxCodeInterface::TYPE_ACCOUNT),
        ]);
        $this->matcher->expects($this->once())
            ->method('match')
            ->with($shippingAddress, $taxCodes)
            ->willReturn([$this->getTaxRule('PRODUCT_TAX_CODE', '0.05')]);

        $this->incTaxCalculator->expects($this->once())
            ->method('calculate')
            ->with(BigDecimal::of(10), BigDecimal::of(0.05))
            ->willReturn(ResultElement::create('10.5', '10', '0.5', '0'));

        $this->resolver->resolve($taxable);
    }

    public function testTaxableWithoutShippingCost()
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTax');

        $this->matcher->expects($this->never())
            ->method('match');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableWithoutAddress()
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('10');
        $taxable->addItem($item);
        $taxable->setTaxationAddress(null);

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTax');

        $this->matcher->expects($this->never())
            ->method('match');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableNegativeShippingCost()
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('-10');
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTax');

        $this->matcher->expects($this->never())
            ->method('match');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testResolve()
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('10');
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(false);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('getShippingTaxCodes')
            ->willReturn(['PRODUCT_TAX_CODE']);

        $taxCodes = new TaxCodes([
            new TaxCode('PRODUCT_TAX_CODE', TaxCodeInterface::TYPE_PRODUCT),
            new TaxCode('ACCOUNT_TAX_CODE', TaxCodeInterface::TYPE_ACCOUNT),
        ]);
        $this->matcher->expects($this->once())
            ->method('match')
            ->with($shippingAddress, $taxCodes)
            ->willReturn([$this->getTaxRule('PRODUCT_TAX_CODE', '0.05')]);

        $this->excTaxCalculator->expects($this->once())
            ->method('calculate')
            ->with(BigDecimal::of(10), BigDecimal::of(0.05))
            ->willReturn(ResultElement::create('10.5', '10', '0.5', '0'));

        $this->resolver->resolve($taxable);
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
}
