<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StartCheckoutForVisitorWithShoppingListTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/ApiFrontendForVisitor/DataFixtures/shopping_list_for_visitor.yml'
        ]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->set('oro_shopping_list.availability_for_guests', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', false);
        $configManager->set('oro_shopping_list.availability_for_guests', false);
        $configManager->flush();
        parent::tearDown();
    }

    public function testStartCheckout(): array
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

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
                        'totalValue' => '49.7900',
                        'totals' => [
                            ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '49.7900']
                        ]
                    ],
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                ['type' => 'checkoutlineitems', 'id' => 'new'],
                                ['type' => 'checkoutlineitems', 'id' => 'new']
                            ]
                        ],
                        'customerUser' => ['data' => null],
                        'customer' => ['data' => null],
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

        return $expectedData;
    }

    /**
     * @depends testStartCheckout
     */
    public function testStartCheckoutWhenCheckoutAlreadyExists(array $expectedData): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout']
        );
        $this->assertResponseContains($expectedData, $response);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }
}
