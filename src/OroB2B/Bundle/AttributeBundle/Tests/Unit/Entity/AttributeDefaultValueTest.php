<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class AttributeDefaultValueTest extends EntityTestCase
{

    public function testProperties()
    {
        $locale = new Locale();
        $locale->setCode('es_MX');

        $attribute = new Attribute();
        $attribute->setType('float');

        $option = new AttributeOption();
        $option->setAttribute($attribute);
        $option->setLocale($locale);

        $properties = [
            ['id', 1],
            ['integer', 3],
            ['string', 'string'],
            ['float', 3.9999],
            ['datetime', new \DateTime('now')],
            ['text', 'text'],
            ['fallback', 'website'],
            ['locale', $locale, false],
            ['attribute', $attribute, false],
            ['option', $option, false],
        ];

        $this->assertPropertyAccessors(new AttributeDefaultValue(), $properties);
    }
}
