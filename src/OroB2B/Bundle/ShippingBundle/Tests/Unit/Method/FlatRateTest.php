<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\FlatRate;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

class FlatRateTest extends \PHPUnit_Framework_TestCase
{
    const CURRENCY = 'USD';
    const PRICE = 25;
    const CHECKOUT_LINE_ITEMS_COUNT = 5;

    /**
     * @var FlatRate
     */
    protected $flatRate;

    protected function setUp()
    {
        $this->flatRate = new FlatRate();
    }

    /**
     * $currency
     * $price
     * $type
     * $expectedPrice
     *
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice($currency, $price, $type, $expectedPrice)
    {
        /** @var ShippingContextAwareInterface|\PHPUnit_Framework_MockObject_MockObject $dataEntity **/
        $dataEntity = $this->getMock(ShippingContextAwareInterface::class);

        /** @var FlatRateRuleConfiguration|\PHPUnit_Framework_MockObject_MockObject $configEntity **/
        $configEntity = $this->getMock(FlatRateRuleConfiguration::class);

        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);

        /** @var ArrayCollection|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $lineItems = $this->getMock(ArrayCollection::class);

        $configEntity->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency)
        ;

        $configEntity->expects($this->once())
            ->method('getPrice')
            ->willReturn($price)
        ;

        $configEntity->expects($this->once())
            ->method('getType')
            ->willReturn($type)
        ;

        $dataEntity->expects($this->any())
            ->method('get')
            ->with('checkout')
            ->willReturn($checkout)
        ;

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lineItems)
        ;

        $lineItems->expects($this->any())
            ->method('count')
            ->willReturn(self::CHECKOUT_LINE_ITEMS_COUNT)
        ;

        $price = $this->flatRate->calculatePrice($dataEntity, $configEntity);
        $this->assertTrue($price instanceof Price);
        $this->assertEquals($expectedPrice, $price->getValue());
    }

    public function ruleConfigProvider()
    {
        return [
            [
                'currency' => self::CURRENCY,
                'price' => self::PRICE,
                'type' => FlatRateRuleConfiguration::TYPE_PER_ORDER,
                'expectedPrice' => self::PRICE
            ],
            [
                'currency' => self::CURRENCY,
                'price' => self::PRICE,
                'type' => FlatRateRuleConfiguration::TYPE_PER_ITEM,
                'expectedPrice' => self::PRICE * self::CHECKOUT_LINE_ITEMS_COUNT
            ]
        ];
    }
}
