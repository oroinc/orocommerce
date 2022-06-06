<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryWithImageData extends AbstractCategoryFixture
{
    public const FIRST_LEVEL = 'category_1';

    /**
     * {@inheritdoc}
     */
    protected $categories = [
        self::FIRST_LEVEL => [],
    ];

    /**
     * {@inheritdoc}
     */
    protected $categoryImages = [
        self::FIRST_LEVEL => ['small' => 'small_image'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function getImageName(string $sku): string
    {
        return sprintf('@OroCatalogBundle/Tests/Functional/DataFixtures/files/%s.png', $sku);
    }
}
