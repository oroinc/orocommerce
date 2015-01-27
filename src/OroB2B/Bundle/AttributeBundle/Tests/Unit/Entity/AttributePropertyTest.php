<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AttributePropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $attributeId = 1;
        $attribute = new AttributeProperty();
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
        $attributeProperty = new AttributeProperty();

        call_user_func_array([$attributeProperty, 'set' . ucfirst($property)], [$value]);

        $this->assertEquals(
            $value,
            call_user_func_array(
                [
                    $attributeProperty,
                    method_exists($attributeProperty, 'get' . ucfirst($property))
                        ? 'get' . ucfirst($property)
                        : 'is' . ucfirst($property)
                ],
                []
            )
        );
    }

    /**
     * @return array
     */
    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');

        return [
            'on_product_view'       => ['onProductView', true],
            'in_product_list'       => ['inProductList', false],
            'use_in_sorting'        => ['useInSorting', true],
            'use_for_search'        => ['useForSearch', true],
            'on_advanced_search'    => ['onAdvancedSearch', false],
            'on_product_comparison' => ['onProductComparison', true],
            'in_filters'            => ['inFilters', true],
            'fallback'              => ['fallback', 'website'],
            'created_at'            => ['createdAt', $now],
            'updated_at'            => ['updatedAt', $now],
        ];
    }

    public function testSetGetWebsite()
    {
        $website = new Website();
        $website->setName('Website');
        $website->setUrl('www.website.com');

        $attributeProperty = new AttributeProperty();
        $attributeProperty->setWebsite($website);

        $this->assertEquals($website, $attributeProperty->getWebsite());
    }

    public function testSetGetAttribute()
    {
        $attribute = new Attribute();
        $attribute->setType('text');

        $attributeProperty = new AttributeProperty();
        $attributeProperty->setAttribute($attribute);

        $this->assertEquals($attribute, $attributeProperty->getAttribute());
    }

    public function testPrePersist()
    {
        $locale = new AttributeProperty();

        $this->assertNull($locale->getCreatedAt());
        $this->assertNull($locale->getUpdatedAt());

        $locale->prePersist();
        $this->assertInstanceOf('\DateTime', $locale->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $locale->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $locale = new AttributeProperty();

        $this->assertNull($locale->getUpdatedAt());

        $locale->preUpdate();
        $this->assertInstanceOf('\DateTime', $locale->getUpdatedAt());
    }
}
