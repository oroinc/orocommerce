<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductOptionsType;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class FixedProductMethodTypeTest extends TestCase
{
    use EntityTrait;

    public const LABEL = 'Fixed Product';

    /**
     * @var FixedProductMethodType
     */
    protected FixedProductMethodType $fixedProductType;

    /**
     * @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected RoundingServiceInterface $roundingService;

    /**
     * @var ShippingCostProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected ShippingCostProvider $shippingCostProvider;

    protected function setUp(): void
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $this->roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->shippingCostProvider = $this->createMock(ShippingCostProvider::class);

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
     * @param array $options
     * @param float $shippingCost
     * @param float $expectedPrice
     *
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

    /**
     * @return array
     */
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
