<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryData extends AbstractCategoryFixture
{
    const FIRST_LEVEL = 'category_1';
    const SECOND_LEVEL1 = 'category_1_2';
    const SECOND_LEVEL2 = 'category_1_5';
    const SECOND_LEVEL3 = 'category_1_8';
    const SECOND_LEVEL4 = 'category_1_9';
    const THIRD_LEVEL1 = 'category_1_2_3';
    const THIRD_LEVEL2 = 'category_1_5_6';
    const THIRD_LEVEL3 = 'category_1_8_10';
    const THIRD_LEVEL4 = 'category_1_9_11';
    const FOURTH_LEVEL1 = 'category_1_2_3_4';
    const FOURTH_LEVEL2 = 'category_1_5_6_7';
    const FOURTH_LEVEL3 = 'category_1_8_10_12';
    const FOURTH_LEVEL4 = 'category_1_8_10_13';
    const FOURTH_LEVEL5 = 'category_1_9_11_14';
    const FOURTH_LEVEL6 = 'category_1_9_11_15';

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
            self::SECOND_LEVEL3 => [
                self::THIRD_LEVEL3 => [
                    self::FOURTH_LEVEL3 => [],
                    self::FOURTH_LEVEL4 => [],
                ],
            ],
            self::SECOND_LEVEL4 => [
                self::THIRD_LEVEL4 => [
                    self::FOURTH_LEVEL5 => [],
                    self::FOURTH_LEVEL6 => [],
                ],
            ],
        ],
    ];
}
