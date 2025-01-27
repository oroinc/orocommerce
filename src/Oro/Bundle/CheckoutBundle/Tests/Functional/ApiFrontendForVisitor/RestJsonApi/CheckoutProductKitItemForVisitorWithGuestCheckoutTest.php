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
class CheckoutProductKitItemForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    private const string ENABLE_GUEST_CHECKOUT = 'oro_checkout.guest_checkout';

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
        // guard
        self::assertFalse(self::getConfigManager()->get(self::ENABLE_GUEST_CHECKOUT));
        $this->setGuestCheckoutOptionValue(true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setGuestCheckoutOptionValue(false);
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

    private function setGuestCheckoutOptionValue(bool $value): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(self::ENABLE_GUEST_CHECKOUT, $value);
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutproductkititemlineitems']);
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
                    ],
                    [
                        'type' => 'checkoutproductkititemlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.3.kit_item.1->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetForDeletedCheckout(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.deleted.line_item.1.kit_item.1->id)>'
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

    public function testTryToGetForUnaccessibleCheckout(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.unaccessible.line_item.2.kit_item.1->id)>'
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
        $this->markTestSkipped('BB-25046');
        $data = [
            'data' => [
                'type' => 'checkoutproductkititemlineitems',
                'attributes' => [
                    'quantity' => 1
                ],
                'relationships' => [
                    'lineItem' => [
                        'data' => [
                            'type' => 'checkoutlineitems',
                            'id' => '<toString(@checkout.in_progress.line_item.2->id)>'
                        ]
                    ],
                    'kitItem' => [
                        'data' => [
                            'type' => 'productkititems',
                            'id' => '<toString(@product-kit-3-kit-item-0->id)>'
                        ]
                    ],
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => '<toString(@product-1->id)>'
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
        $response = $this->post(['entity' => 'checkoutproductkititemlineitems'], $data);
        $this->assertResponseContains($data, $response);
    }

    public function testTryToCreateForUnaccessibleCheckout(): void
    {
        $this->markTestSkipped('BB-25046');
        $response = $this->post(
            ['entity' => 'checkoutproductkititemlineitems'],
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'attributes' => [
                        'quantity' => 1
                    ],
                    'relationships' => [
                        'lineItem' => [
                            'data' => [
                                'type' => 'checkoutlineitems',
                                'id' => '<toString(@checkout.unaccessible.line_item.2->id)>'
                            ]
                        ],
                        'kitItem' => [
                            'data' => [
                                'type' => 'productkititems',
                                'id' => '<toString(@product-kit-3-kit-item-0->id)>'
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'products',
                                'id' => '<toString(@product-1->id)>'
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
                'source' => ['pointer' => '/data/relationships/lineItem/data']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testUpdate(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemId,
                'attributes' => [
                    'quantity' => 2
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateForDeletedCheckout(): void
    {
        $kitItemId = $this->getReference('checkout.deleted.line_item.1.kit_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'id' => (string)$kitItemId,
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
        $kitItemId = $this->getReference('checkout.unaccessible.line_item.2.kit_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'id' => (string)$kitItemId,
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
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $this->delete(['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId]);
        $kitItem = $this->getEntityManager()->find(CheckoutLineItem::class, $kitItemId);
        self::assertTrue(null === $kitItem);
    }

    public function testTryToDeleteForDeletedCheckout(): void
    {
        $response = $this->delete(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.deleted.line_item.1.kit_item.1->id)>'
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

    public function testTryToDeleteForUnaccessibleCheckout(): void
    {
        $response = $this->delete(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.unaccessible.line_item.2.kit_item.1->id)>'
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
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $this->cdelete(['entity' => 'checkoutproductkititemlineitems'], ['filter[id]' => (string)$kitItemId]);
        $kitItem = $this->getEntityManager()->find(CheckoutLineItem::class, $kitItemId);
        self::assertTrue(null === $kitItem);
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'lineItem'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => '<toString(@checkout.in_progress.line_item.2->id)>',
                    'attributes' => ['productSku' => 'product-kit-3']
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationship(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'lineItem'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => '<toString(@checkout.in_progress.line_item.2->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'lineItem'
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
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'lineItem'
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
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'lineItem'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
