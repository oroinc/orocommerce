<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;

/**
 * @dbIsolationPerTest
 */
class PriceRuleUpdateListTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceRules::class]);
    }

    public function testCreateEntities()
    {
        $data = [
            'data' => [
                [
                    'type'          => 'pricerules',
                    'attributes'    => [
                        'currency'              => 'EUR',
                        'currencyExpression'    => null,
                        'quantity'              => 1,
                        'quantityExpression'    => null,
                        'productUnitExpression' => null,
                        'ruleCondition'         => 'product.category.id == 1',
                        'rule'                  => 'pricelist[1].prices.value * 0.8',
                        'priority'              => 5
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.box->code)>'
                            ]
                        ],
                        'priceList'   => [
                            'data' => [
                                'type' => 'pricelists',
                                'id'   => '<toString(@price_list_3->id)>'
                            ]
                        ]
                    ]
                ],
                [
                    'type'          => 'pricerules',
                    'attributes'    => [
                        'currency'              => 'EUR',
                        'currencyExpression'    => null,
                        'quantity'              => 1,
                        'quantityExpression'    => null,
                        'productUnitExpression' => null,
                        'ruleCondition'         => 'product.category.id == 2',
                        'rule'                  => 'pricelist[1].prices.value * 1.3',
                        'priority'              => 10
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.bottle->code)>'
                            ]
                        ],
                        'priceList'   => [
                            'data' => [
                                'type' => 'pricelists',
                                'id'   => '<toString(@price_list_2->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceRule::class, $data);

        $response = $this->cget(
            ['entity' => 'pricerules'],
            ['filter[id][gt]' => '@price_list_3_price_rule_5->id']
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            $expectedData['data'][$key]['id'] = 'new';
        }
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateEntities()
    {
        $rule1Id = $this->getReference('price_list_1_price_rule_1')->getId();
        $rule2Id = $this->getReference('price_list_3_price_rule_5')->getId();

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'pricerules',
                    'id'         => (string)$rule1Id,
                    'attributes' => [
                        'rule' => 'pricelist[1].prices.value * 0.2'
                    ]
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => 'pricerules',
                    'id'         => (string)$rule2Id,
                    'attributes' => [
                        'rule' => 'pricelist[1].prices.value - 2'
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceRule::class, $data);

        $response = $this->cget(
            ['entity' => 'pricerules'],
            ['filter' => ['id' => [(string)$rule1Id, (string)$rule2Id]]]
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            unset($expectedData['data'][$key]['meta']);
        }
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateAndUpdateEntities()
    {
        $ruleId = $this->getReference('price_list_3_price_rule_5')->getId();

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'pricerules',
                    'id'         => (string)$ruleId,
                    'attributes' => [
                        'rule' => 'pricelist[1].prices.value - 2'
                    ]
                ],
                [
                    'type'          => 'pricerules',
                    'attributes'    => [
                        'currency'              => 'EUR',
                        'currencyExpression'    => null,
                        'quantity'              => 1,
                        'quantityExpression'    => null,
                        'productUnitExpression' => null,
                        'ruleCondition'         => 'product.category.id == 1',
                        'rule'                  => 'pricelist[1].prices.value * 0.8',
                        'priority'              => 5
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.box->code)>'
                            ]
                        ],
                        'priceList'   => [
                            'data' => [
                                'type' => 'pricelists',
                                'id'   => '<toString(@price_list_3->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceRule::class, $data);

        $response = $this->cget(
            ['entity' => 'pricerules'],
            ['filter[id][gte]' => '@price_list_3_price_rule_5->id']
        );
        $expectedData['data'][0]['id'] = 'new';
        unset($expectedData['data'][1]['meta']);
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateEntitiesWithIncludes()
    {
        $data = [
            'data'     => [
                [
                    'type'          => 'pricerules',
                    'attributes'    => [
                        'currency'      => 'EUR',
                        'quantity'      => 1,
                        'ruleCondition' => 'product.category.id == 1',
                        'rule'          => 'pricelist[1].prices.value * 0.8',
                        'priority'      => 5
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.box->code)>'
                            ]
                        ],
                        'priceList'   => [
                            'data' => [
                                'type' => 'pricelists',
                                'id'   => 'pricelist1'
                            ]
                        ]
                    ]
                ],
                [
                    'type'          => 'pricerules',
                    'attributes'    => [
                        'currency'      => 'EUR',
                        'quantity'      => 1,
                        'ruleCondition' => 'product.category.id == 2',
                        'rule'          => 'pricelist[1].prices.value * 1.3',
                        'priority'      => 10
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.bottle->code)>'
                            ]
                        ],
                        'priceList'   => [
                            'data' => [
                                'type' => 'pricelists',
                                'id'   => 'pricelist2'
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'pricelists',
                    'id'         => 'pricelist1',
                    'attributes' => [
                        'name'                  => 'New Price List 1',
                        'priceListCurrencies'   => ['EUR'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => true
                    ]
                ],
                [
                    'type'       => 'pricelists',
                    'id'         => 'pricelist2',
                    'attributes' => [
                        'name'                  => 'New Price List 2',
                        'priceListCurrencies'   => ['EUR'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => false
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceRule::class, $data);

        $response = $this->cget(
            ['entity' => 'pricerules'],
            ['filter[id][gt]' => '@price_list_3_price_rule_5->id', 'include' => 'priceList']
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            $expectedData['data'][$key]['id'] = 'new';
            $expectedData['data'][$key]['relationships']['priceList']['data']['id'] = 'new';
        }
        foreach ($expectedData['included'] as $key => $item) {
            $expectedData['included'][$key]['id'] = 'new';
        }
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
