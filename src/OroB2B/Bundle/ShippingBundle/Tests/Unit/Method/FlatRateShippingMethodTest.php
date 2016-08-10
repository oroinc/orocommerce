<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

class FlatRateShippingMethodTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FlatRateShippingMethod
     */
    protected $flatRate;

    protected function setUp()
    {
        $this->flatRate = new FlatRateShippingMethod();
    }

    /**
     * @param string $currency
     * @param Price $price
     * @param float $handlingFeeValue
     * @param string $type
     * @param float $expectedPrice
     *
     * @dataProvider ruleConfigProvider
     */
    public function testCalculatePrice($currency, $price, $handlingFeeValue, $type, $expectedPrice)
    {
        /** @var ShippingContextAwareInterface|\PHPUnit_Framework_MockObject_MockObject $dataEntity **/
        $dataEntity = $this->getMock(ShippingContextAwareInterface::class);

        /** @var FlatRateRuleConfiguration|object $configEntity **/
        $configEntity = $this->getEntity(
            FlatRateRuleConfiguration::class,
            [
                'currency' => $currency,
                'price' => $price,
                'type' => $type,
                'handlingFeeValue' => $handlingFeeValue
            ]
        );

        /** @var ArrayCollection|null|\PHPUnit_Framework_MockObject_MockObject $lineItems **/
        $lineItems = $this->getMock(ArrayCollection::class);

        $context = ['lineItems' => $lineItems];

        $dataEntity->expects($this->any())
            ->method('getShippingContext')
            ->willReturn($context)
        ;

        $lineItems->expects($this->any())
            ->method('count')
            ->willReturn(5)
        ;

        $price = $this->flatRate->calculatePrice($dataEntity, $configEntity);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals($expectedPrice, $price->getValue());
    }

    /**
     * @return array
     */
    public function ruleConfigProvider()
    {
        return [
            [
                'currency' => 'USD',
                'price' => Price::create(25, 'USD'),
                'handlingFeeValue' => 5,
                'type' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER,
                'expectedPrice' => 30
            ],
            [
                'currency' => 'USD',
                'price' => Price::create(25, 'USD'),
                'handlingFeeValue' => 15,
                'type' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ITEM,
                'expectedPrice' => 140
            ],
            [
                'currency' => 'EUR',
                'price' => Price::create(25, 'EUR'),
                'handlingFeeValue' => 3,
                'type' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER,
                'expectedPrice' => 28
            ],
            [
                'currency' => 'EUR',
                'price' => Price::create(25, 'EUR'),
                'handlingFeeValue' => 25,
                'type' => FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ITEM,
                'expectedPrice' => 150
            ]
        ];
    }
}
