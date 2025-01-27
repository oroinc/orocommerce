<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StartCheckoutForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/DataFixtures/shopping_list.yml'
        ]);
    }

    public function testStartCheckout(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout']
        );
        $expectedData = $this->updateResponseContent(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => 'new',
                    'attributes' => [
                        'poNumber' => null,
                        'shippingMethod' => null,
                        'shippingMethodType' => null,
                        'paymentMethod' => null,
                        'shipUntil' => null,
                        'customerNotes' => 'Shopping List 1 Notes',
                        'currency' => 'USD',
                        'completed' => false,
                        'totalValue' => '59.1500',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '59.1500']
                        ]
                    ],
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new']
                            ]
                        ],
                        'customerUser' => [
                            'data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']
                        ],
                        'customer' => [
                            'data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']
                        ],
                        'billingAddress' => ['data' => null],
                        'shippingAddress' => ['data' => null],
                        'source' => [
                            'data' => ['type' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>']
                        ],
                        'order' => ['data' => null]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
    }
}
