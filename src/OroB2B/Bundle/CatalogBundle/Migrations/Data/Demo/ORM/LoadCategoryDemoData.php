<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryDemoData extends AbstractCategoryFixture
{
    /**
     * @var array
     */
    protected $categories = [
        'Uniforms' => [
            'Healthcare' => [
                'Medical Scrubs' => [],
                'Lab Coats' => [],
                'Patient Gowns' => [],
                'Counter Coats' => [],
            ],
        ],
    ];
}
