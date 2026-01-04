<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

/**
 * Load Product fixtures
 */
class LoadProductData extends AbstractLoadProductData
{
    public const PRODUCT_1 = 'product-1';
    public const PRODUCT_2 = 'product-2';
    public const PRODUCT_3 = 'product-3';
    public const PRODUCT_4 = 'product-4';
    public const PRODUCT_5 = 'product-5';
    public const PRODUCT_6 = 'product-6';
    public const PRODUCT_7 = 'продукт-7';
    public const PRODUCT_8 = 'product-8';
    public const PRODUCT_9 = 'продукт-9';

    public const PRODUCT_1_DEFAULT_NAME = 'product-1.names.default';
    public const PRODUCT_2_DEFAULT_NAME = 'product-2.names.default';
    public const PRODUCT_3_DEFAULT_NAME = 'product-3.names.default';
    public const PRODUCT_4_DEFAULT_NAME = 'product-4.names.default';
    public const PRODUCT_5_DEFAULT_NAME = 'product-5.names.default';
    public const PRODUCT_6_DEFAULT_NAME = 'product-6.names.default';
    public const PRODUCT_7_DEFAULT_NAME = 'продукт-7.names.default';
    public const PRODUCT_8_DEFAULT_NAME = 'product-8.names.default';
    public const PRODUCT_9_DEFAULT_NAME = 'продукт-9.names.default';

    public const PRODUCT_1_DEFAULT_SLUG_PROTOTYPE = 'product-1.slugPrototypes.default';
    public const PRODUCT_2_DEFAULT_SLUG_PROTOTYPE = 'product-2.slugPrototypes.default';
    public const PRODUCT_3_DEFAULT_SLUG_PROTOTYPE = 'product-3.slugPrototypes.default';
    public const PRODUCT_4_DEFAULT_SLUG_PROTOTYPE = 'product-4.slugPrototypes.default';
    public const PRODUCT_5_DEFAULT_SLUG_PROTOTYPE = 'product-5.slugPrototypes.default';
    public const PRODUCT_6_DEFAULT_SLUG_PROTOTYPE = 'product-6.slugPrototypes.default';
    public const PRODUCT_7_DEFAULT_SLUG_PROTOTYPE = 'продукт-7.slugPrototypes.default';
    public const PRODUCT_8_DEFAULT_SLUG_PROTOTYPE = 'product-8.slugPrototypes.default';
    public const PRODUCT_9_DEFAULT_SLUG_PROTOTYPE = 'продукт-9.slugPrototypes.default';

    #[\Override]
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'product_fixture.yml';
    }
}
