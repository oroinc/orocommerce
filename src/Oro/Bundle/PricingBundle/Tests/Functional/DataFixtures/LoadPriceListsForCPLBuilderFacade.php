<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadPriceListsForCPLBuilderFacade extends LoadPriceLists
{
    /**
     * @var array
     */
    protected static $priceListNames = [
        'PL_WS1',
        'PL_WS2',
        'PL_WS1_CG1',
        'PL_WS1_CG2',
        'PL_WS2_CG1',
        'PL_WS2_CG2',
        'PL_WS1_C11',
        'PL_WS1_C12',
        'PL_WS2_C11',
        'PL_WS2_C12',
        'PL_WS1_C21',
        'PL_WS1_C22',
        'PL_WS2_C21',
        'PL_WS2_C22',
        'PL_WS1_C3',
        'PL_WS2_C3',
        'PL_WS1_C4',
        'PL_WS2_C4',
    ];

    /**
     * {@inheritDoc}
     */
    public static function getPriceListData()
    {
        $data = [];
        foreach (self::$priceListNames as $priceListName) {
            $data[] = [
                'name' => $priceListName,
                'reference' => $priceListName,
                'default' => false,
                'currencies' => ['USD'],
                'active' => true,
                'assignmentRule' => null,
            ];
        }

        return $data;
    }
}
