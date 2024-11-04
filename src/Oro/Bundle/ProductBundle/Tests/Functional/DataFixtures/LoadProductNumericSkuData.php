<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

class LoadProductNumericSkuData extends AbstractLoadProductData
{
    #[\Override]
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'product_numeric_sku_fixture.yml';
    }
}
