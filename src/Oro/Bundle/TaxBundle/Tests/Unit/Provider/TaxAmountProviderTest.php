<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaxAmountProviderTest extends TestCase
{
    private \stdClass $sourceEntity;

    private TaxProviderInterface|MockObject $taxProvider;

    private TaxationSettingsProvider|MockObject $taxationSettingsProvider;

    private TaxAmountProvider $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->sourceEntity = new \stdClass();
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);

        $taxProviderRegistry
            ->expects($this->atLeastOnce())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new TaxAmountProvider($taxProviderRegistry, $this->taxationSettingsProvider);
    }

    /**
     * @dataProvider getTaxAmountDataProvider
     */
    public function testGetTaxAmount(float $taxAmount, float $expected): void
    {
        $taxResultElement = new ResultElement([
            ResultElement::TAX_AMOUNT => $taxAmount
        ]);

        $taxResult = new Result([
            Result::TOTAL => $taxResultElement,
        ]);

        $this->taxProvider
            ->expects($this->once())
            ->method('loadTax')
            ->with($this->sourceEntity)
            ->willReturn($taxResult);

        $actual = $this->provider->getTaxAmount($this->sourceEntity);
        $this->assertSame($expected, $actual);
    }

    public function getTaxAmountDataProvider(): array
    {
        return [
            'amount should stay the same' => [10.0, 10.0],
            'zero should be zero' => [ 0, 0.0],
            'small amount in 1e-6 should be considered as 0' => [ 0.000001, 0.0],
        ];
    }

    /**
     * @dataProvider getTaxTotalShippingTaxProvider
     */
    public function testGetExcludedTaxAmount(
        bool $isProductPricesIncludeTax,
        bool $isShippingRatesIncludeTax,
        int $tax,
        int $shippingTax,
        float $expectedTax
    ): void {
        $entity = new Order();

        $taxShipping = [ResultElement::TAX_AMOUNT => $shippingTax, AbstractResultElement::CURRENCY => 'USD'];
        $tax = [ResultElement::TAX_AMOUNT => $tax, AbstractResultElement::CURRENCY => 'USD'];

        $taxResult = Result::jsonDeserialize(
            [
                Result::SHIPPING => $taxShipping,
                Result::TAXES => [$tax]
            ]
        );

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn($isProductPricesIncludeTax);
        $this->taxationSettingsProvider->expects(self::once())
            ->method('isShippingRatesIncludeTaxWithEntity')
            ->willReturn($isShippingRatesIncludeTax);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->with($entity)
            ->willReturn($taxResult);

        $actualTaxAmount = $this->provider->getExcludedTaxAmount($entity);
        $this->assertSame($expectedTax, $actualTaxAmount);
    }

    public function getTaxTotalShippingTaxProvider(): array
    {
        return [
            'Both product and shipping not included tax' => [
                'isProductPricesIncludeTax' => false,
                'isShippingRatesIncludeTax' => false,
                'tax' => 2,
                'shippingTax' => 1,
                'expectedTax' => 3.0
            ],
            'Shipping rate not included tax' => [
                'isProductPricesIncludeTax' => true,
                'isShippingRatesIncludeTax' => false,
                'tax' => 3,
                'shippingTax' => 1,
                'expectedTax' => 1.0
            ],
            'Product subtotal not included tax' => [
                'isProductPricesIncludeTax' => false,
                'isShippingRatesIncludeTax' => true,
                'tax' => 2,
                'shippingTax' => 1,
                'expectedTax' => 2.0
            ]
        ];
    }

    /**
     * @param mixed $exceptionClass
     * @throws TaxationDisabledException
     * @dataProvider getTaxAmountWithUnHandledExceptionDataProvider
     */
    public function testGetTaxAmountWithUnHandledException(string $exceptionClass): void
    {
        $this->taxProvider
            ->expects($this->once())
            ->method('loadTax')
            ->with($this->sourceEntity)
            ->willThrowException(new $exceptionClass());

        $this->expectException($exceptionClass);

        $this->provider->getTaxAmount($this->sourceEntity);
    }

    public function getTaxAmountWithUnHandledExceptionDataProvider(): array
    {
        return [
            [TaxationDisabledException::class],
            [UnmappableArgumentException::class],
            [\InvalidArgumentException::class],
            [\Exception::class],
        ];
    }
}
