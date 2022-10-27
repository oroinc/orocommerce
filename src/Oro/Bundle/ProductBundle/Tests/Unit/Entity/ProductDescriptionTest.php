<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductDescriptionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $this->assertPropertyAccessors(
            new ProductDescription(),
            [
                ['id', '123'],
                ['fallback', 'test'],
                ['wysiwyg', 'test wysiwyg'],
                ['wysiwygStyle', 'test style'],
                ['wysiwygProperties', ['data']],
                ['localization',  new Localization()],
                ['product', new Product()],
            ]
        );
    }
}
