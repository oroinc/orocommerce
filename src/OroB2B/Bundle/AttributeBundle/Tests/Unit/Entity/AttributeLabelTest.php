<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class AttributeLabelTest extends EntityTestCase
{

    public function testProperties()
    {
        $locale = new Locale();
        $locale->setCode('es_MX');

        $attribute = new Attribute();
        $attribute->setType('string');

        $properties = [
            ['id', 1],
            ['value', 'Test label'],
            ['fallback', 'website'],
            ['locale', $locale, false],
            ['attribute', $attribute, false],
        ];

        $this->assertPropertyAccessors(new AttributeLabel(), $properties);
    }
}
