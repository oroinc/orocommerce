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
        'Lighting Products'           => ['small' => '1'],
        'Architectural Floodlighting' => ['small' => '2'],
        'Headlamps'                   => ['small' => '3'],
        'Medical Uniforms'            => ['small' => '4'],
        'Office Furniture'            => ['small' => '5'],
        'Retail Supplies'             => ['small' => '6'],
        'POS Systems'                 => ['small' => '7'],
        'Printers'                    => ['small' => '8'],
        'Medical Apparel'             => ['large' => '9_large'],
    ];
}
