<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryDemoData extends AbstractCategoryFixture
{
    const CATEGORY_REFERENCE_PREFIX = 'category_demo_data';

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

    /**
     * @return string
     */
    protected function getReferencePrefix()
    {
        return self::CATEGORY_REFERENCE_PREFIX;
    }
}
