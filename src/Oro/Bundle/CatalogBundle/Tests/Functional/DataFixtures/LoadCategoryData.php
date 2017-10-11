<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryData extends AbstractCategoryFixture
{
    const FIRST_LEVEL = 'category_1';
    const SECOND_LEVEL1 = 'category_1_2';
    const SECOND_LEVEL2 = 'category_1_5';
    const THIRD_LEVEL1 = 'category_1_2_3';
    const THIRD_LEVEL2 = 'category_1_5_6';
    const FOURTH_LEVEL1 = 'category_1_2_3_4';
    const FOURTH_LEVEL2 = 'category_1_5_6_7';

    /**
     * @var array
     */
    protected $categories = [
        self::FIRST_LEVEL => [
            self::SECOND_LEVEL1 => [
                self::THIRD_LEVEL1 => [
                    self::FOURTH_LEVEL1 => [],
                ],
            ],
            self::SECOND_LEVEL2 => [
                self::THIRD_LEVEL2 => [
                    self::FOURTH_LEVEL2 => [],
                ],
            ],
        ],
    ];
}
