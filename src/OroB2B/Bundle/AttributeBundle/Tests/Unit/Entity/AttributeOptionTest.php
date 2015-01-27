<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class AttributeOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $attributeId = 1;
        $attribute = new AttributeOption();
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
        $attributeLabel = new AttributeOption();

        call_user_func_array([$attributeLabel, 'set' . ucfirst($property)], [$value]);

        $this->assertEquals($value, call_user_func_array([$attributeLabel, 'get' . ucfirst($property)], []));
    }

    /**
     * @return array
     */
    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');

        return [
            'value'      => ['value', 'test'],
            'order'      => ['order', 5],
            'fallback'   => ['fallback', 'website'],
            'created_at' => ['createdAt', $now],
            'updated_at' => ['updatedAt', $now],
        ];
    }

    public function testSetGetLocale()
    {
        $locale = new Locale();
        $locale->setCode('es_MX');

        $option = new AttributeOption();
        $option->setLocale($locale);

        $this->assertEquals($locale, $option->getLocale());
    }

    public function testSetGetAttribute()
    {
        $attribute = new Attribute();
        $attribute->setType('select');

        $option = new AttributeOption();
        $option->setAttribute($attribute);

        $this->assertEquals($attribute, $option->getAttribute());
    }

    public function testPrePersist()
    {
        $locale = new AttributeOption();

        $this->assertNull($locale->getCreatedAt());
        $this->assertNull($locale->getUpdatedAt());

        $locale->prePersist();
        $this->assertInstanceOf('\DateTime', $locale->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $locale->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $locale = new AttributeOption();

        $this->assertNull($locale->getUpdatedAt());

        $locale->preUpdate();
        $this->assertInstanceOf('\DateTime', $locale->getUpdatedAt());
    }
}
