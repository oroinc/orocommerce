<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductOptionsType;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FixedProductMethodTypeTest extends TestCase
{
    private const LABEL = 'Fixed Product';

    private RoundingServiceInterface|MockObject $roundingService;

    private ShippingCostProvider|MockObject $shippingCostProvider;

    private FixedProductMethodType $fixedProductType;

    #[\Override]
    protected function setUp(): void
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->shippingCostProvider = $this->createMock(ShippingCostProvider::class);

        $this->roundingService->expects(self::any())
            ->method('getPrecision')
            ->willReturn(4);
        $this->roundingService->expects(self::any())
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
        self::assertEquals(FixedProductMethodType::IDENTIFIER, $this->fixedProductType->getIdentifier());
    }

    public function testGetLabel(): void
    {
        self::assertEquals(self::LABEL, $this->fixedProductType->getLabel());
    }

    public function testGetOptionsConfigurationFormType(): void
    {
        self::assertEquals(
            FixedProductOptionsType::class,
            $this->fixedProductType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder(): void
    {
        self::assertEquals(0, $this->fixedProductType->getSortOrder());
    }

    /**
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice(array $options, float $shippingCost, float $expectedPrice): void
    {
        $context = new ShippingContext([
            ShippingContext::FIELD_SUBTOTAL => Price::create(100, 'USD'),
            ShippingContext::FIELD_LINE_ITEMS => new ArrayCollection([]),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $this->roundingService->expects(self::any())
            ->method('round')
            ->with($expectedPrice)
            ->willReturn($expectedPrice);

        $this->shippingCostProvider->expects(self::any())
            ->method('getCalculatedProductShippingCost')
            ->with($context->getLineItems(), $context->getCurrency())
            ->willReturn($shippingCost);

        $price = $this->fixedProductType->calculatePrice($context, [], $options);

        self::assertInstanceOf(Price::class, $price);
        self::assertEquals($expectedPrice, $price->getValue());
        self::assertEquals($context->getCurrency(), $price->getCurrency());
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
