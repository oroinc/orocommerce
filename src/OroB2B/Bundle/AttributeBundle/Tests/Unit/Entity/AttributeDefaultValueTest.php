<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class AttributeDefaultValueTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $attributeId = 1;
        $attribute = new AttributeDefaultValue();
        $this->assertNull($attribute->getId());

        $class = new \ReflectionClass($attribute);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($attribute, $attributeId);

        $this->assertEquals($attributeId, $attribute->getId());
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGetSet($property, $value)
    {
        $attributeLabel = new AttributeDefaultValue();

        call_user_func_array([$attributeLabel, 'set' . ucfirst($property)], [$value]);

        $this->assertEquals($value, call_user_func_array([$attributeLabel, 'get' . ucfirst($property)], []));
    }

    /**
     * @return array
     */
    public function flatPropertiesDataProvider()
    {
        return [
            'integer'    => ['integer', 3],
            'string'     => ['string', 'string'],
            'float'      => ['float', 3.14],
            'datetime'   => ['datetime', new \DateTime('now')],
            'text'       => ['text', 'text'],
            'fallback'   => ['fallback', 'website']
        ];
    }

    public function testSetGetLocale()
    {
        $locale = new Locale();
        $locale->setCode('es_MX');

        $defaultValue = new AttributeDefaultValue();
        $defaultValue->setLocale($locale);

        $this->assertEquals($locale, $defaultValue->getLocale());
    }

    public function testSetGetOption()
    {
        $attribute = new Attribute();
        $attribute->setType('float');

        $locale = new Locale();
        $locale->setCode('es_MX');

        $option = new AttributeOption();
        $option->setAttribute($attribute);
        $option->setLocale($locale);

        $defaultValue = new AttributeDefaultValue();
        $defaultValue->setOption($option);

        $this->assertEquals($option, $defaultValue->getOption());
    }

    public function testSetGetAttribute()
    {
        $attribute = new Attribute();
        $attribute->setType('float');

        $defaultValue = new AttributeDefaultValue();
        $defaultValue->setAttribute($attribute);

        $this->assertEquals($attribute, $defaultValue->getAttribute());
    }
}
