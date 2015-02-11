<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AttributePropertyTest extends EntityTestCase
{
    public function testProperties()
    {
        $website = new Website();
        $website->setName('Website');

        $attribute = new Attribute();
        $attribute->setType('text');

        $properties = [
            ['id', 1],
            ['attribute', $attribute, false],
            ['website', $website, false],
            ['website', null],
            ['field', AttributeProperty::FIELD_ON_PRODUCT_VIEW],
            ['value', true],
            ['value', null],
            ['fallback', FallbackType::SYSTEM],
        ];

        $this->assertPropertyAccessors(new AttributeProperty(), $properties);
    }
}
