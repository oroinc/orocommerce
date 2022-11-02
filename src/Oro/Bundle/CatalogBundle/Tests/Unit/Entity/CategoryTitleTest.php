<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CategoryTitleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $this->assertPropertyAccessors(
            new CategoryShortDescription(),
            [
                ['id', '123'],
                ['fallback', 'test'],
                ['string', 'test string'],
                ['localization',  new Localization()],
                ['category', new Category()],
            ]
        );
    }
}
