<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryImportExportHelperData extends AbstractCategoryFixture
{
    public const FIRST_LEVEL = 'category_1';
    public const SECOND_LEVEL1 = 'category_1_2';
    public const THIRD_LEVEL1 = 'category_1_2_3';
    public const THIRD_LEVEL2 = 'category1/2/3';
    public const THIRD_LEVEL3 = 'category1 / 2 / 3';

    /**
     * @var array
     */
    protected $categories = [
        self::FIRST_LEVEL => [
            self::SECOND_LEVEL1 => [
                self::THIRD_LEVEL1 => [],
                self::THIRD_LEVEL2 => [],
                self::THIRD_LEVEL3 => [],
            ],
        ],
    ];
}
