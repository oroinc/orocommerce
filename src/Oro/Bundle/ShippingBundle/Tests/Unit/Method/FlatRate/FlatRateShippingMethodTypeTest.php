<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\FlatRate;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingMethodTypeOptionsType;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Component\Testing\Unit\EntityTrait;

class FlatRateShippingMethodTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FlatRateShippingMethodType
     */
    protected $flatRateType;

    protected function setUp()
    {
        $this->flatRateType = new FlatRateShippingMethodType();
    }

    public function testGetIdentifier()
    {
        static::assertEquals(FlatRateShippingMethodType::IDENTIFIER, $this->flatRateType->getIdentifier());
    }

    public function testGetLabel()
    {
        static::assertEquals('oro.shipping.method.flat_rate.type.label', $this->flatRateType->getLabel());
    }

    public function testGetOptionsConfigurationFormType()
    {
        static::assertEquals(
            FlatRateShippingMethodTypeOptionsType::class,
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
                    FlatRateShippingMethodType::PRICE_OPTION => 25,
                    FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ORDER_TYPE,
                    FlatRateShippingMethodType::HANDLING_FEE_OPTION => 5,
                ],
                'expectedPrice' => 30
            ],
            [
                'currency' => 'EUR',
                'options' => [
                    FlatRateShippingMethodType::PRICE_OPTION => 15,
                    FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ITEM_TYPE,
                    FlatRateShippingMethodType::HANDLING_FEE_OPTION => 3,
                ],
                'expectedPrice' => 78
            ],
        ];
    }
}
