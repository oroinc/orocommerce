<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductOptionsType;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;

class FixedProductMethodTypeTest extends \PHPUnit\Framework\TestCase
{
    private const LABEL = 'Fixed Product';

    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    /** @var ShippingCostProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostProvider;

    /** @var FixedProductMethodType */
    private $fixedProductType;

    protected function setUp(): void
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->shippingCostProvider = $this->createMock(ShippingCostProvider::class);

        $this->roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $this->roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->fixedProductType = new FixedProductMethodType(
            self::LABEL,
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    public function testGetIdentifier(): void
    {
        $this->assertEquals(FixedProductMethodType::IDENTIFIER, $this->fixedProductType->getIdentifier());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(self::LABEL, $this->fixedProductType->getLabel());
    }

    public function testGetOptionsConfigurationFormType(): void
    {
        $this->assertEquals(
            FixedProductOptionsType::class,
            $this->fixedProductType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder(): void
    {
        $this->assertEquals(0, $this->fixedProductType->getSortOrder());
    }

    /**
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice(array $options, float $shippingCost, float $expectedPrice): void
    {
        $context = new ShippingContext([
            ShippingContext::FIELD_SUBTOTAL => Price::create(100, 'USD'),
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([]),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->roundingService->expects($this->any())
            ->method('round')
            ->with($expectedPrice)
            ->willReturn($expectedPrice);

        $this->shippingCostProvider->expects($this->any())
            ->method('getCalculatedProductShippingCost')
            ->with($context->getLineItems(), $context->getCurrency())
            ->willReturn($shippingCost);

        $price = $this->fixedProductType->calculatePrice($context, [], $options);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals($expectedPrice, $price->getValue());
        $this->assertEquals($context->getCurrency(), $price->getCurrency());
    }

    public function ruleConfigProvider(): array
    {
        return [
            [
                'options' => [
                    FixedProductMethodType::SURCHARGE_AMOUNT => 10.0,
                    FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::PERCENT,
                    FixedProductMethodType::SURCHARGE_ON     => FixedProductMethodType::PRODUCT_PRICE,
                ],
                'shippingCost' => 3.0,
                'expectedPrice' => 13.0
            ],
            [
                'options' => [
                    FixedProductMethodType::SURCHARGE_AMOUNT => 10.0,
                    FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::PERCENT,
                    FixedProductMethodType::SURCHARGE_ON     => FixedProductMethodType::PRODUCT_SHIPPING_COST,
                ],
                'shippingCost' => 4.0,
                'expectedPrice' => 4.4
            ],
            [
                'options' => [
                    FixedProductMethodType::SURCHARGE_AMOUNT => 10.0,
                    FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::FIXED_AMOUNT
                ],
                'shippingCost' => 5.0,
                'expectedPrice' => 15.0
            ],
        ];
    }
}
