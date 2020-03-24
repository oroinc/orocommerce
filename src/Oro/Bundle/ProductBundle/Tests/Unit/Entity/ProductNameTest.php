<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductNameTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $this->assertPropertyAccessors(
            new ProductName(),
            [
                ['id', '123'],
                ['fallback', 'test'],
                ['string', 'text string'],
                ['localization',  new Localization()],
                ['product', new Product()],
            ]
        );
    }
}
