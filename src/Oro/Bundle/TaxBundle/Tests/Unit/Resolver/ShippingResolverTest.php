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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingResolverTest extends TestCase
{
    private TaxCalculatorInterface|MockObject $excTaxCalculator;

    private TaxCalculatorInterface|MockObject $incTaxCalculator;

    private MatcherInterface|MockObject $matcher;

    private TaxationSettingsProvider|MockObject $taxationSettingsProvider;

    private ShippingResolver $resolver;

    protected function setUp(): void
    {
        $this->excTaxCalculator = $this->createMock(TaxCalculatorInterface::class);
        $this->incTaxCalculator = $this->createMock(TaxCalculatorInterface::class);
        $this->matcher = $this->createMock(MatcherInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->resolver = new ShippingResolver(
            $this->incTaxCalculator,
            $this->excTaxCalculator,
            $this->matcher,
            $this->taxationSettingsProvider
        );
    }

    public function testTaxableWithoutItems(): void
    {
        $taxable = new Taxable();

        $this->matcher->expects($this->never())
            ->method('match');
        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTaxWithEntity');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableResultLocked(): void
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->addItem($item);
        $taxable->getResult()->lockResult();

        $this->matcher->expects($this->never())
            ->method('match');
        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTaxWithEntity');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testShippingRatesIncludeTaxes(): void
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('10');
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTaxWithEntity')
            ->willReturn(true);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('getShippingTaxCodesWithEntity')
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

    public function testTaxableWithoutShippingCost(): void
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTaxWithEntity');

        $this->matcher->expects($this->never())
            ->method('match');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableWithoutAddress(): void
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('10');
        $taxable->addItem($item);
        $taxable->setTaxationAddress(null);

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTaxWithEntity');

        $this->matcher->expects($this->never())
            ->method('match');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableNegativeShippingCost(): void
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('-10');
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->never())
            ->method('isShippingRatesIncludeTaxWithEntity');

        $this->matcher->expects($this->never())
            ->method('match');
        $this->incTaxCalculator->expects($this->never())
            ->method('calculate');
        $this->excTaxCalculator->expects($this->never())
            ->method('calculate');

        $this->resolver->resolve($taxable);
    }

    public function testResolve(): void
    {
        $taxable = new Taxable();
        $item = new Taxable();
        $taxable->setShippingCost('10');
        $taxable->addItem($item);
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'ACCOUNT_TAX_CODE');

        $shippingAddress = new ShippingAddressStub();
        $taxable->setTaxationAddress($shippingAddress);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTaxWithEntity')
            ->willReturn(false);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('getShippingTaxCodesWithEntity')
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

    private function getTaxRule(string $taxCode, string $taxRate): TaxRule
    {
        $tax = new Tax();
        $tax->setRate($taxRate);
        $tax->setCode($taxCode);

        $taxRule = new TaxRule();
        $taxRule->setTax($tax);

        return $taxRule;
    }
}
