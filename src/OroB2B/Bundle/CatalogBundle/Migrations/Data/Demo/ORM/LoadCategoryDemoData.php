<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryDemoData extends AbstractCategoryFixture
{
    /**
     * @var array
     */
    protected $categories = [
        'Test First Level' => [
            'Test Second Level 1' => ['Test Third Level 1' => [], 'Test Third Level 2' => []],
            'Test Second Level 2' => [],
        ],
    ];
}
