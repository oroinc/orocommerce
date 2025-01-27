<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ValidateCheckoutProductKitItemLineItemTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class
        ]);
    }

    public function testTryToCreateDuplicatedKitItemLineItem(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutproductkititemlineitems'],
            'create_checkout_kit_item_duplicate.yml',
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unique collection item constraint',
                'detail' => 'Product kit line item must contain only unique kit item line items.'
            ],
            $response
        );
    }

    public function testTryToUpdateDuplicatedKitItemLineItem(): void
    {
        $response = $this->patch(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>'
            ],
            'update_checkout_kit_item_duplicate.yml',
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unique collection item constraint',
                'detail' => 'Product kit line item must contain only unique kit item line items.'
            ],
            $response
        );
    }

    public function testTryToUpdateForCompletedCheckout(): void
    {
        $kitItemId = $this->getReference('checkout.completed.line_item.2.kit_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'id' => (string)$kitItemId,
                    'attributes' => [
                        'quantity' => 10
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The completed checkout cannot be changed.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateForPaymentInProgressCheckout(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $em = $this->getEntityManager();
        $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(true);
        $em->flush();
        try {
            $response = $this->patch(
                ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
                [
                    'data' => [
                        'type' => 'checkoutproductkititemlineitems',
                        'id' => (string)$kitItemId,
                        'attributes' => [
                            'quantity' => 10
                        ]
                    ]
                ],
                [],
                false
            );
            $this->assertResponseValidationError(
                [
                    'title' => 'access denied exception',
                    'detail' => 'The checkout cannot be changed as the payment is being processed.'
                ],
                $response,
                Response::HTTP_FORBIDDEN
            );
        } finally {
            $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(false);
            $em->flush();
        }
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
                        'quantity' => 10
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
}
