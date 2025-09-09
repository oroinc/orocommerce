<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadShoppingListPromotionalDiscountsData;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class ShoppingListPromotionDiscountsTest extends FrontendRestJsonApiTestCase
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
            ['entity' => 'shoppinglists'],
            [
                'filter' => [
                    'id' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list5->id)>'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list1->id)>',
                        'attributes' => [
                            'name' => 'Shopping List 1',
                            'currency' => 'USD',
                            'total' => '57.1500',
                            'subTotal' => '59.1500',
                            'discount' => '-2.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list5->id)>',
                        'attributes' => [
                            'name' => 'Shopping List 5',
                            'currency' => 'USD',
                            'total' => '0.2300',
                            'subTotal' => '1.2300',
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
            ['entity' => 'shoppinglists'],
            [
                'filter' => [
                    'id' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list5->id)>'
                    ]
                ],
                'fields[shoppinglists]' => 'total'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list1->id)>',
                        'attributes' => [
                            'total' => '57.1500'
                        ]
                    ],
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list5->id)>',
                        'attributes' => [
                            'total' => '0.2300'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithSubTotalValueOnly(): void
    {
        $response = $this->cget(
            ['entity' => 'shoppinglists'],
            [
                'filter' => [
                    'id' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list5->id)>'
                    ]
                ],
                'fields[shoppinglists]' => 'subTotal'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list1->id)>',
                        'attributes' => [
                            'subTotal' => '59.1500'
                        ]
                    ],
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list5->id)>',
                        'attributes' => [
                            'subTotal' => '1.2300'
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
            ['entity' => 'shoppinglists'],
            [
                'filter' => [
                    'id' => [
                        '<toString(@shopping_list1->id)>',
                        '<toString(@shopping_list5->id)>'
                    ]
                ],
                'fields[shoppinglists]' => 'discount'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list1->id)>',
                        'attributes' => [
                            'discount' => '-2.0000'
                        ]
                    ],
                    [
                        'type' => 'shoppinglists',
                        'id' => '<toString(@shopping_list5->id)>',
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
