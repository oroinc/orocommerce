<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

/**
 * Load Product fixtures for search functionality testing
 */
class LoadProductSearchData extends AbstractLoadProductData
{
    public const YELLOW_PINE = 'yellow-pine';
    public const RED_OAK = 'red-oak';
    public const BLUE_SPRUCE = 'blue-spruce';

    #[\Override]
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'product_search_fixture.yml';
    }
}
