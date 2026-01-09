<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadShoppingListPromotionalDiscountsData;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class ShoppingListItemPromotionDiscountsTest extends FrontendRestJsonApiTestCase
{
    private ?int $initialShoppingListLimit;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadShoppingListPromotionalDiscountsData::class
        ]);

        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()->get('oro_shopping_list.manager.shopping_list_total');
        for ($i = 1; $i <= 3; $i++) {
            $totalManager->recalculateTotals($this->getReference('shopping_list' . $i), true);
        }

        $this->initialShoppingListLimit = self::getConfigManager()->get('oro_shopping_list.shopping_list_limit');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        if ($configManager->get('oro_shopping_list.shopping_list_limit') !== $this->initialShoppingListLimit) {
            $configManager->set('oro_shopping_list.shopping_list_limit', $this->initialShoppingListLimit);
            $configManager->flush();
        }
        parent::tearDown();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            [
                'filter' => [
                    'shoppingList' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list2->id)>'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item1->id)>',
                        'attributes' => [
                            'quantity' => 2,
                            'currency' => 'USD',
                            'value' => '14.8000',
                            'subTotal' => '29.6000',
                            'totalValue' => '28.6000',
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item2->id)>',
                        'attributes' => [
                            'quantity' => 2,
                            'currency' => 'USD',
                            'value' => '14.8000',
                            'subTotal' => '29.6000',
                            'totalValue' => '28.6000',
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item1->id)>',
                        'attributes' => [
                            'quantity' => 5,
                            'currency' => 'USD',
                            'value' => '1.2300',
                            'subTotal' => '6.1500',
                            'totalValue' => '5.1500',
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item2->id)>',
                        'attributes' => [
                            'quantity' => 10,
                            'currency' => 'USD',
                            'value' => '2.3400',
                            'subTotal' => '23.4000',
                            'totalValue' => '23.4000',
                            'discount' => '0.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item3->id)>',
                        'attributes' => [
                            'quantity' => 20,
                            'currency' => 'USD',
                            'value' => '1.0100',
                            'subTotal' => '20.2000',
                            'totalValue' => '19.2000',
                            'discount' => '-1.0000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithTotalValueOnly(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            [
                'filter' => [
                    'shoppingList' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list2->id)>'
                    ]
                ],
                'fields[shoppinglistitems]' => 'totalValue'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item1->id)>',
                        'attributes' => [
                            'totalValue' => '28.6000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item2->id)>',
                        'attributes' => [
                            'totalValue' => '28.6000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item1->id)>',
                        'attributes' => [
                            'totalValue' => '5.1500'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item2->id)>',
                        'attributes' => [
                            'totalValue' => '23.4000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item3->id)>',
                        'attributes' => [
                            'totalValue' => '19.2000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithDiscountOnly(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            [
                'filter' => [
                    'shoppingList' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list2->id)>'
                    ]
                ],
                'fields[shoppinglistitems]' => 'discount'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item1->id)>',
                        'attributes' => [
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@kit_line_item2->id)>',
                        'attributes' => [
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item1->id)>',
                        'attributes' => [
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item2->id)>',
                        'attributes' => [
                            'discount' => '0.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglistitems',
                        'id' => '<toString(@line_item3->id)>',
                        'attributes' => [
                            'discount' => '-1.0000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
