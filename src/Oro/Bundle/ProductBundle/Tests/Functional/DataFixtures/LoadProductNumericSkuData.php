<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

class LoadProductNumericSkuData extends AbstractLoadProductData
{
    /**
     * {@inheritDoc]
     */
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'product_numeric_sku_fixture.yml';
    }
}
