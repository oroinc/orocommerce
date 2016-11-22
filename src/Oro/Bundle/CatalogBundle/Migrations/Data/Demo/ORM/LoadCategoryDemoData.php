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
        'Medical Apparel'   => [],
        'Office Furniture'  => [],
        'Retail Supplies'   => [],
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
        'Lighting Products'           => '2JD90',
        'Medical Apparel'             => '8BC37',
        'Office Furniture'            => '5UB78',
        'Retail Supplies'             => '5XY10',
        'Architectural Floodlighting' => '7BS72',
        'Headlamps'                   => '6UK81'
    ];
}
