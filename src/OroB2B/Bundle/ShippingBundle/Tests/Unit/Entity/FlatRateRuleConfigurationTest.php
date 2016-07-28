<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;

class FlatRateRuleConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $entity = new FlatRateRuleConfiguration();
        $properties = [
            ['value', 1.0],
            ['currency', 'USD'],
            ['price', Price::create(1.0, 'USD')],
        ];

        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testCreatePrice()
    {
        $entity = new FlatRateRuleConfiguration();
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $entity->setCurrency('USD');
        $entity->createPrice();
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPriceNotInitializedWithValueWithoutCurrency()
    {
        $entity = new FlatRateRuleConfiguration();
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $this->assertEmpty($entity->getPrice());
    }

    public function testPriceNotInitializedWithCurrencyWithoutValue()
    {
        $entity = new FlatRateRuleConfiguration();
        $this->assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        $this->assertEmpty($entity->getPrice());
    }

    public function testCreatePriceCalledOnSetCurrency()
    {
        $entity = new FlatRateRuleConfiguration();
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $this->assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testCreatePriceCalledOnSetValue()
    {
        $entity = new FlatRateRuleConfiguration();
        $this->assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPrePersist()
    {
        $entity = new FlatRateRuleConfiguration();
        $entity->setPrice(Price::create(42, 'USD'));
        $this->assertEquals(42, $entity->getValue());
        $this->assertEquals('USD', $entity->getCurrency());
        $entity->getPrice()->setValue(84);
        $entity->getPrice()->setCurrency('EUR');

        $entity->updatePrice();
        $this->assertEquals(84, $entity->getValue());
        $this->assertEquals('EUR', $entity->getCurrency());
    }

    public function testToString()
    {
        $entity = new FlatRateRuleConfiguration();
        $entity->setValue(42);
        $entity->setCurrency('USD');
        $entity->setMethod('UPS');
        $entity->setType('TEST');
        $this->assertEquals('UPS, TEST, 42 USD', (string)$entity);
    }
}
