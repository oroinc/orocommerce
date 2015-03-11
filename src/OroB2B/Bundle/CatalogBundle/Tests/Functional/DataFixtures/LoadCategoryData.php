<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryData extends AbstractCategoryFixture
{
    /**
     * @var array
     */
    protected $categories = [
        'Test First Level' => [
            'Test Second Level' => [
                'Test Third Level 1' => [],
                'Test Third Level 2' => []
            ]
        ],
    ];
}
