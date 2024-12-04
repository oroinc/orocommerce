<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

/**
 * Load Product fixtures
 */
class LoadProductDataWithAttributeFamilies extends LoadProductData
{
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'product_fixture_with_attribute_families.yml';
    }
}
