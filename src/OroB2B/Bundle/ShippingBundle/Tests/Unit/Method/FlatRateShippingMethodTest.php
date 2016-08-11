<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

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

    public function testGetName()
    {
        static::assertEquals(FlatRateShippingMethod::NAME, $this->flatRate->getName());
    }

    public function testGetLabel()
    {
        static::assertEquals('Flat Rate', $this->flatRate->getLabel());
    }

    public function testGetShippingTypes()
    {
        static::assertEmpty($this->flatRate->getShippingTypes());
    }

    public function testGetRuleConfigurationClass()
    {
        static::assertEquals(FlatRateRuleConfiguration::class, $this->flatRate->getRuleConfigurationClass());
    }

    public function testGetFormType()
    {
        static::assertEquals(FlatRateShippingConfigurationType::NAME, $this->flatRate->getFormType());
    }

    public function testGetShippingTypeLabel()
    {
        static::assertNull($this->flatRate->getShippingTypeLabel('anyType'));
    }

    public function testGetOptions()
    {
        static::assertEmpty($this->flatRate->getOptions([]));
    }

    public function testGetSortOrder()
    {
        static::assertEquals(10, $this->flatRate->getSortOrder());
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
        /** @var FlatRateRuleConfiguration|object $configEntity **/
        $configEntity = $this->getEntity(
            FlatRateRuleConfiguration::class,
            [
                'currency' => $currency,
                'price' => $price,
                'processingType' => $type,
                'handlingFeeValue' => $handlingFeeValue
            ]
        );

        $lineItem = $this->getEntity(LineItem::class, ['quantity' => 5]);
        /** @var ArrayCollection|null|\PHPUnit_Framework_MockObject_MockObject $lineItems **/
        $lineItems = $this->getEntity(ArrayCollection::class, [], [$lineItem]);

        /** @var ShippingContextAwareInterface|\PHPUnit_Framework_MockObject_MockObject $shippingContext */
        $shippingContext = $this->getMock(ShippingContextAwareInterface::class);

        $shippingContext->expects(static::any())
            ->method('getShippingContext')
            ->willReturn(['line_items' => $lineItems])
        ;

        $price = $this->flatRate->calculatePrice($shippingContext, $configEntity);

        static::assertInstanceOf(Price::class, $price);
        static::assertEquals($expectedPrice, $price->getValue());
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
