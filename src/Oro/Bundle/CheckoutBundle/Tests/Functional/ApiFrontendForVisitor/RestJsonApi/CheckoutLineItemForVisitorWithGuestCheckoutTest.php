<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadGuestCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutLineItemForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCustomerUserData::class,
            LoadGuestCheckoutData::class
        ]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', false);
        $configManager->flush();

        parent::tearDown();
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();
        $visitor = $this->getVisitor();
        $visitor->setCustomerUser($this->getReference('customer_user'));
        $guestCheckoutReferences = ['checkout.in_progress', 'checkout.deleted'];
        foreach ($guestCheckoutReferences as $checkoutReference) {
            /** @var Checkout $checkout */
            $checkout = $this->getReference($checkoutReference);
            $checkout->getSource()->getShoppingList()->addVisitor($visitor);
        }
        $this->getEntityManager()->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutlineitems']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.1->id)>'
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.2->id)>'
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.3->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => '<toString(@checkout.in_progress.line_item.1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetForDeletedCheckout(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.deleted.line_item.1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForUnaccessibleCheckout(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.unaccessible.line_item.1->id)>'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'attributes' => [
                    'quantity' => 1
                ],
                'relationships' => [
                    'checkout' => [
                        'data' => [
                            'type' => 'checkouts',
                            'id' => '<toString(@checkout.in_progress->id)>'
                        ]
                    ],
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => '<toString(@product-2->id)>'
                        ]
                    ],
                    'productUnit' => [
                        'data' => [
                            'type' => 'productunits',
                            'id' => '<toString(@product_unit.milliliter->code)>'
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'checkoutlineitems'], $data);
        $this->assertResponseContains($data, $response);
    }

    public function testTryToCreateForUnaccessibleCheckout(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'attributes' => [
                        'quantity' => 1
                    ],
                    'relationships' => [
                        'checkout' => [
                            'data' => [
                                'type' => 'checkouts',
                                'id' => '<toString(@checkout.unaccessible->id)>'
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'products',
                                'id' => '<toString(@product-2->id)>'
                            ]
                        ],
                        'productUnit' => [
                            'data' => [
                                'type' => 'productunits',
                                'id' => '<toString(@product_unit.milliliter->code)>'
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access granted constraint',
                'detail' => 'The "VIEW" permission is denied for the related resource.',
                'source' => ['pointer' => '/data/relationships/checkout/data']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testUpdate(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'id' => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 5
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateForDeletedCheckout(): void
    {
        $lineItemId = $this->getReference('checkout.deleted.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 5
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateForUnaccessibleCheckout(): void
    {
        $lineItemId = $this->getReference('checkout.unaccessible.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 5
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDelete(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $this->delete(['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId]);
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }

    public function testTryToDeleteForDeletedCheckout(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.deleted.line_item.1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteForUnaccessibleCheckout(): void
    {
        $response = $this->delete(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.unaccessible.line_item.1->id)>'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDeleteList(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $this->cdelete(['entity' => 'checkoutlineitems'], ['filter[id]' => (string)$lineItemId]);
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2->id)>',
                'association' => 'kitItemLineItems'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutproductkititemlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                        'attributes' => ['quantity' => 1]
                    ],
                    [
                        'type' => 'checkoutproductkititemlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.2->id)>',
                        'attributes' => ['quantity' => 1]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationship(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2->id)>',
                'association' => 'kitItemLineItems'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutproductkititemlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>'
                    ],
                    [
                        'type' => 'checkoutproductkititemlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.2->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationship(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationship(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
