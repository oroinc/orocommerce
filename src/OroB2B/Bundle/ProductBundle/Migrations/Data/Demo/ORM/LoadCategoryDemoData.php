<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryDemoData extends AbstractCategoryFixture
{
    /**
     * @var array
     */
    protected $categories = [
        'Models' => [
            'Cars' => ['Classic Cars' => [], 'Vintage Cars' => []],
            'Motorcycles' => [],
            'Trucks and Buses' => [],
            'Planes' => [],
            'Ships' => [],
            'Trains' => [],
        ],
    ];
}
