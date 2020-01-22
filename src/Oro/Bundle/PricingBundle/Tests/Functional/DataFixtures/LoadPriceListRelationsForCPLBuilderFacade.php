<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadPriceListRelationsForCPLBuilderFacade extends LoadPriceListRelations
{
    /**
     * @var array
     */
    protected $data = [
        LoadWebsiteData::WEBSITE1 => [
            'priceLists' => [
                [
                    'reference' => 'PL_WS1_REL',
                    'priceList' => 'PL_WS1',
                    'sort_order' => 100,
                    'mergeAllowed' => true
                ]
            ],
            'priceListsToCustomerGroups' => [
                'customer_group.group1' => [
                    [
                        'reference' => 'PL_WS1_CG1_REL',
                        'priceList' => 'PL_WS1_CG1',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer_group.group2' => [
                    [
                        'reference' => 'PL_WS1_CG2_REL',
                        'priceList' => 'PL_WS1_CG2',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ],
                ]
            ],
            'priceListsToCustomers' => [
                'customer.level_1' => [
                    [
                        'reference' => 'PL_WS1_C11_REL',
                        'priceList' => 'PL_WS1_C11',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.3' => [
                    [
                        'reference' => 'PL_WS1_C12_REL',
                        'priceList' => 'PL_WS1_C12',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.2' => [
                    [
                        'reference' => 'PL_WS1_C21_REL',
                        'priceList' => 'PL_WS1_C21',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.2.1' => [
                    [
                        'reference' => 'PL_WS1_C22_REL',
                        'priceList' => 'PL_WS1_C22',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1_1' => [
                    [
                        'reference' => 'PL_WS1_C3_REL',
                        'priceList' => 'PL_WS1_C3',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.1.1' => [
                    [
                        'reference' => 'PL_WS1_C4_REL',
                        'priceList' => 'PL_WS1_C4',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
            ],
        ],
        LoadWebsiteData::WEBSITE2 => [
            'priceLists' => [
                [
                    'reference' => 'PL_WS2_REL',
                    'priceList' => 'PL_WS2',
                    'sort_order' => 100,
                    'mergeAllowed' => true
                ]
            ],
            'priceListsToCustomerGroups' => [
                'customer_group.group1' => [
                    [
                        'reference' => 'PL_WS2_CG1_REL',
                        'priceList' => 'PL_WS2_CG1',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer_group.group2' => [
                    [
                        'reference' => 'PL_WS2_CG2_REL',
                        'priceList' => 'PL_WS2_CG2',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ],
                ]
            ],
            'priceListsToCustomers' => [
                'customer.level_1' => [
                    [
                        'reference' => 'PL_WS2_C11_REL',
                        'priceList' => 'PL_WS2_C11',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.3' => [
                    [
                        'reference' => 'PL_WS2_C12_REL',
                        'priceList' => 'PL_WS2_C12',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.2' => [
                    [
                        'reference' => 'PL_WS2_C21_REL',
                        'priceList' => 'PL_WS2_C21',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.2.1' => [
                    [
                        'reference' => 'PL_WS2_C22_REL',
                        'priceList' => 'PL_WS2_C22',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1_1' => [
                    [
                        'reference' => 'PL_WS2_C3_REL',
                        'priceList' => 'PL_WS2_C3',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.1.1' => [
                    [
                        'reference' => 'PL_WS2_C4_REL',
                        'priceList' => 'PL_WS2_C4',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceListFallbackSettingsForCPLBuilderFacade::class,
            LoadPriceListsForCPLBuilderFacade::class
        ];
    }
}
