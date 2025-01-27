<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadGuestCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
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
        $response = $this->cget(['entity' => 'checkouts']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkouts',
                        'id' => '<toString(@checkout.in_progress->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => '<toString(@checkout.in_progress->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetForDeleted(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.deleted->id)>'],
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

    public function testTryToGetForUnaccessible(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.unaccessible->id)>'],
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

    public function testTryToCreate(): void
    {
        $shoppingListId = $this->getReference('checkout.in_progress.shopping_list')->getId();
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'relationships' => [
                        'source' => [
                            'data' => [
                                'type' => 'shoppinglists',
                                'id' => (string)$shoppingListId
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
                'title' => 'guest checkout constraint',
                'detail' => 'The guest checkout must have a source entity.'
            ],
            $response
        );
    }

    public function testTryToCreateWithoutSource(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            ['data' => ['type' => 'checkouts']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'guest checkout constraint',
                'detail' => 'The guest checkout must have a source entity.'
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $data = [
            'data' => [
                'type' => 'checkouts',
                'id' => (string)$checkoutId,
                'attributes' => [
                    'poNumber' => 'updated_po_number'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateForDeleted(): void
    {
        $checkoutId = $this->getReference('checkout.deleted')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'poNumber' => 'new_po_number'
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

    public function testTryToUpdateForUnaccessible(): void
    {
        $checkoutId = $this->getReference('checkout.unaccessible')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'poNumber' => 'new_po_number'
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
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $this->delete(['entity' => 'checkouts', 'id' => (string)$checkoutId]);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertTrue(null === $checkout);
    }

    public function testTryToDeleteForDeleted(): void
    {
        $response = $this->delete(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.deleted->id)>'],
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

    public function testTryToDeleteForUnaccessible(): void
    {
        $response = $this->delete(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.unaccessible->id)>'],
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
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $this->cdelete(['entity' => 'checkouts'], ['filter[id]' => (string)$checkoutId]);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertTrue(null === $checkout);
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'association' => 'lineItems'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                        'attributes' => ['productSku' => 'product-1']
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.2->id)>',
                        'attributes' => ['productSku' => 'product-kit-3']
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.in_progress.line_item.3->id)>',
                        'attributes' => ['productSku' => 'product-kit-2']
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
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'association' => 'lineItems'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>'],
                    ['type' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.2->id)>'],
                    ['type' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.3->id)>']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'association' => 'lineItems'
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
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'association' => 'lineItems'
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
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'association' => 'lineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
