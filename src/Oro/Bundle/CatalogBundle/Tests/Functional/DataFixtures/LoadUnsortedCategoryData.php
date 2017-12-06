<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadUnsortedCategoryData extends AbstractCategoryFixture
{
    const FIRST_LEVEL1 = 'Seats';
    const FIRST_LEVEL2 = 'Brushes';
    const SECOND_LEVEL1 = 'Sets & Kits';
    const SECOND_LEVEL2 = 'All Brushes';

    /**
     * @var array
     */
    protected $categories = [
        self::FIRST_LEVEL1 => [],
        self::FIRST_LEVEL2 => [
            self::SECOND_LEVEL1 => [],
            self::SECOND_LEVEL2 => [],
        ]
    ];
}
