<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CategoryLongDescriptionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $this->assertPropertyAccessors(
            new CategoryLongDescription(),
            [
                ['id', '123'],
                ['fallback', 'test'],
                ['wysiwyg', 'test wysiwyg'],
                ['wysiwygStyle', 'test style'],
                ['wysiwygProperties', ['data']],
                ['localization',  new Localization()],
                ['category', new Category()],
            ]
        );
    }
}
