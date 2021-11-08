<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

/**
 * Load Product fixtures
 */
class LoadProductData extends AbstractLoadProductData
{
    const PRODUCT_1 = 'product-1';
    const PRODUCT_2 = 'product-2';
    const PRODUCT_3 = 'product-3';
    const PRODUCT_4 = 'product-4';
    const PRODUCT_5 = 'product-5';
    const PRODUCT_6 = 'product-6';
    const PRODUCT_7 = 'продукт-7';
    const PRODUCT_8 = 'product-8';
    const PRODUCT_9 = 'продукт-9';

    const PRODUCT_1_DEFAULT_NAME = 'product-1.names.default';
    const PRODUCT_2_DEFAULT_NAME = 'product-2.names.default';
    const PRODUCT_3_DEFAULT_NAME = 'product-3.names.default';
    const PRODUCT_4_DEFAULT_NAME = 'product-4.names.default';
    const PRODUCT_5_DEFAULT_NAME = 'product-5.names.default';
    const PRODUCT_6_DEFAULT_NAME = 'product-6.names.default';
    const PRODUCT_7_DEFAULT_NAME = 'продукт-7.names.default';
    const PRODUCT_8_DEFAULT_NAME = 'product-8.names.default';
    const PRODUCT_9_DEFAULT_NAME = 'продукт-9.names.default';

    const PRODUCT_1_DEFAULT_SLUG_PROTOTYPE = 'product-1.slugPrototypes.default';
    const PRODUCT_2_DEFAULT_SLUG_PROTOTYPE = 'product-2.slugPrototypes.default';
    const PRODUCT_3_DEFAULT_SLUG_PROTOTYPE = 'product-3.slugPrototypes.default';
    const PRODUCT_4_DEFAULT_SLUG_PROTOTYPE = 'product-4.slugPrototypes.default';
    const PRODUCT_5_DEFAULT_SLUG_PROTOTYPE = 'product-5.slugPrototypes.default';
    const PRODUCT_6_DEFAULT_SLUG_PROTOTYPE = 'product-6.slugPrototypes.default';
    const PRODUCT_7_DEFAULT_SLUG_PROTOTYPE = 'продукт-7.slugPrototypes.default';
    const PRODUCT_8_DEFAULT_SLUG_PROTOTYPE = 'product-8.slugPrototypes.default';
    const PRODUCT_9_DEFAULT_SLUG_PROTOTYPE = 'продукт-9.slugPrototypes.default';

    /**
     * {@inheritDoc]
     */
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'product_fixture.yml';
    }
}
