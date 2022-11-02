<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateOptionsType;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class FlatRateMethodTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @internal */
    const LABEL = 'Flat Rate';

    /** @var FlatRateMethodType */
    protected $flatRateType;

    protected function setUp(): void
    {
        $this->flatRateType = new FlatRateMethodType(self::LABEL);
    }

    public function testGetIdentifier()
    {
        static::assertEquals(FlatRateMethodType::IDENTIFIER, $this->flatRateType->getIdentifier());
    }

    public function testGetLabel()
    {
        static::assertEquals(self::LABEL, $this->flatRateType->getLabel());
    }

    public function testGetOptionsConfigurationFormType()
    {
        static::assertEquals(
            FlatRateOptionsType::class,
            $this->flatRateType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        static::assertEquals(0, $this->flatRateType->getSortOrder());
    }

    /**
     * @param array $currency
     * @param array $options
     * @param float $expectedPrice
     *
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice($currency, array $options, $expectedPrice)
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

        static::assertInstanceOf(Price::class, $price);
        static::assertEquals($expectedPrice, $price->getValue());
        static::assertEquals($context->getCurrency(), $price->getCurrency());
    }

    /**
     * @return array
     */
    public function ruleConfigProvider()
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
