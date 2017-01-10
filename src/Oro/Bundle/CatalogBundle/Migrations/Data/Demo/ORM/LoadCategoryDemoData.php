<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryDemoData extends AbstractCategoryFixture
{
    /**
     * @var array
     */
    protected $categories = [
        'Lighting Products' => [
            'Architectural Floodlighting' => [],
            'Headlamps'                   => []
        ],
        'Medical Apparel'   => [
            'Medical Uniforms' => []
        ],
        'Office Furniture'  => [],
        'Retail Supplies'   => [
            'POS Systems' => [],
            'Printers'    => []
        ],
        //'Uniforms' => [
        //    'Healthcare' => [
        //        'Medical Scrubs' => [],
        //        'Lab Coats' => [],
        //        'Patient Gowns' => [],
        //        'Counter Coats' => [],
        //    ],
        //],
        //'Identification' => [
        //    'Medical Identification Tags' => [],
        //],
        //'Patient Transport Equipment' => [
        //    'Hospital Wheelchairs' => [],
        //],
    ];

    /**
     * @var array
     */
    protected $categoryImages = [
        'Lighting Products'           => '1',
        'Architectural Floodlighting' => '2',
        'Headlamps'                   => '3',
        'Medical Uniforms'            => '4',
        'Office Furniture'            => '5',
        'Retail Supplies'             => '6',
        'POS Systems'                 => '7',
        'Printers'                    => '8'
    ];
}
