<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\CategoryTitle;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class CategoryTitleTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['category', new Category()],
            ['locale', new Locale()],
            ['value', 'title'],
            ['fallback', FallbackType::SYSTEM],
        ];

        $this->assertPropertyAccessors(new CategoryTitle(), $properties);
    }
}
