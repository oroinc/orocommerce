<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateOptionsType;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class FlatRateMethodTypeTest extends \PHPUnit\Framework\TestCase
{
    private const LABEL = 'Flat Rate';

    private FlatRateMethodType $flatRateType;

    protected function setUp(): void
    {
        $this->flatRateType = new FlatRateMethodType(self::LABEL);
    }

    public function testGetIdentifier()
    {
        self::assertEquals(FlatRateMethodType::IDENTIFIER, $this->flatRateType->getIdentifier());
    }

    public function testGetLabel()
    {
        self::assertEquals(self::LABEL, $this->flatRateType->getLabel());
    }

    public function testGetOptionsConfigurationFormType()
    {
        self::assertEquals(
            FlatRateOptionsType::class,
            $this->flatRateType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        self::assertEquals(0, $this->flatRateType->getSortOrder());
    }

    /**
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice(string $currency, array $options, float $expectedPrice)
    {
        $shippingLineItems = [
            new ShippingLineItem([ShippingLineItem::FIELD_QUANTITY => 3]),
            new ShippingLineItem([ShippingLineItem::FIELD_QUANTITY => 2])
        ];

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => $currency
        ]);

        $price = $this->flatRateType->calculatePrice($context, [], $options);

        self::assertInstanceOf(Price::class, $price);
        self::assertEquals($expectedPrice, $price->getValue());
        self::assertEquals($context->getCurrency(), $price->getCurrency());
    }

    public function ruleConfigProvider(): array
    {
        return [
            [
                'currency' => 'USD',
                'options' => [
                    FlatRateMethodType::PRICE_OPTION => 25,
                    FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ORDER_TYPE,
                    FlatRateMethodType::HANDLING_FEE_OPTION => 5,
                ],
                'expectedPrice' => 30
            ],
            [
                'currency' => 'EUR',
                'options' => [
                    FlatRateMethodType::PRICE_OPTION => 15,
                    FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ITEM_TYPE,
                    FlatRateMethodType::HANDLING_FEE_OPTION => 3,
                ],
                'expectedPrice' => 78
            ],
        ];
    }
}
