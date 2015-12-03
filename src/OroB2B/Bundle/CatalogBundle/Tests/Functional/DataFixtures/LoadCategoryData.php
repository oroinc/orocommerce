<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryData extends AbstractCategoryFixture
{
    const FIRST_LEVEL = 'Test First Level';
    const SECOND_LEVEL1 = 'Test Second Level 1';
    const SECOND_LEVEL2 = 'Test Second Level 2';
    const THIRD_LEVEL1 = 'Test Third Level 1';
    const THIRD_LEVEL2 = 'Test Third Level 2';
    const FOURTH_LEVEL1 = 'Test Fourth Level 1';
    const FOURTH_LEVEL2 = 'Test Fourth Level 2';

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
