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
     * $handlingFee
     * $type
     * $expectedPrice
     *
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice($currency, $price, $handlingFee, $type, $expectedPrice)
    {
        /** @var ShippingContextAwareInterface|\PHPUnit_Framework_MockObject_MockObject $dataEntity **/
        $dataEntity = $this->getMock(ShippingContextAwareInterface::class);

        /** @var FlatRateRuleConfiguration|\PHPUnit_Framework_MockObject_MockObject $configEntity **/
        $configEntity = $this->getMock(FlatRateRuleConfiguration::class);

        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $checkout = $this->getMock(Checkout::class);

        /** @var ArrayCollection|\PHPUnit_Framework_MockObject_MockObject $checkout **/
        $lineItems = $this->getMock(ArrayCollection::class);

        $context = ['checkout' => $checkout];

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

        $configEntity->expects($this->once())
            ->method('getHandlingFee')
            ->willReturn($handlingFee)
        ;

        $dataEntity->expects($this->any())
            ->method('getShippingContext')
            ->willReturn($context)
        ;

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lineItems)
        ;

        $lineItems->expects($this->any())
            ->method('count')
            ->willReturn(5)
        ;

        $price = $this->flatRate->calculatePrice($dataEntity, $configEntity);
        $this->assertTrue($price instanceof Price);
        $this->assertEquals($expectedPrice, $price->getValue());
    }

    public function ruleConfigProvider()
    {
        return [
            [
                'currency' => 'USD',
                'price' => Price::create(25, 'USD'),
                'handlingFee' => Price::create(5, 'USD'),
                'type' => FlatRateRuleConfiguration::TYPE_PER_ORDER,
                'expectedPrice' => 30
            ],
            [
                'currency' => 'USD',
                'price' => Price::create(25, 'USD'),
                'handlingFee' => Price::create(5, 'USD'),
                'type' => FlatRateRuleConfiguration::TYPE_PER_ITEM,
                'expectedPrice' => 130
            ],
            [
                'currency' => 'EUR',
                'price' => Price::create(25, 'EUR'),
                'handlingFee' => Price::create(5, 'EUR'),
                'type' => FlatRateRuleConfiguration::TYPE_PER_ORDER,
                'expectedPrice' => 30
            ],
            [
                'currency' => 'EUR',
                'price' => Price::create(25, 'EUR'),
                'handlingFee' => Price::create(5, 'EUR'),
                'type' => FlatRateRuleConfiguration::TYPE_PER_ITEM,
                'expectedPrice' => 130
            ]
        ];
    }
}
