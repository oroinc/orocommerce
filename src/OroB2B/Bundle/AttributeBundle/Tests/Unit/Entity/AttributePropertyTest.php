<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
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
            ['inFilters', true],
            ['useForSearch', true],
            ['useInSorting', true],
            ['onProductComparison', true],
            ['onProductView', true],
            ['inProductList', false],
            ['onAdvancedSearch', false],
            ['fallback', 'website'],
        ];

        $this->assertPropertyAccessors(new AttributeProperty(), $properties);
    }
}
