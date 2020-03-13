<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductShortDescriptionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $this->assertPropertyAccessors(
            new ProductShortDescription(),
            [
                ['id', '123'],
                ['fallback', 'test'],
                ['text', 'test text'],
                ['localization',  new Localization()],
                ['product', new Product()],
            ]
        );
    }
}
