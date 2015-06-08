<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CurrencyBundle\Model\Price;

class PriceTest extends \PHPUnit_Framework_TestCase
{
    const VALUE = 100;
    const CURRENCY = 'USD';

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Price value can not be empty
     */
    public function testEmptyValueException()
    {
        (new Price())->setValue(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Price currency can not be empty
     */
    public function testEmptyCurrencyException()
    {
        (new Price())->setCurrency(null);
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Price();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            ['value', self::VALUE],
            ['currency', self::CURRENCY]
        ];
    }

    public function testCreate()
    {
        $price = Price::create(self::VALUE, self::CURRENCY);
        $this->assertInstanceOf('Oro\Bundle\CurrencyBundle\Model\Price', $price);
        $this->assertEquals(self::VALUE, $price->getValue());
        $this->assertEquals(self::CURRENCY, $price->getCurrency());
    }
}
