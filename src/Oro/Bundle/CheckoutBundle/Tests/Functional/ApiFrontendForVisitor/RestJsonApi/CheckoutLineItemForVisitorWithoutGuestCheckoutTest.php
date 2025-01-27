<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadGuestCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutLineItemForVisitorWithoutGuestCheckoutTest extends FrontendRestJsonApiTestCase
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

    public function testTryToGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutlineitems'], [], [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreate(): void
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
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdate(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
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
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutlineitems'],
            ['filter[id]' => '<toString(@checkout.in_progress.line_item.1->id)>'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationship(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
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
