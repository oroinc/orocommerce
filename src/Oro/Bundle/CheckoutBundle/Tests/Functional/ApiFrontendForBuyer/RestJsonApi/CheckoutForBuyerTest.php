<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkouts']);
        $this->assertResponseContains('cget_checkout.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'checkouts', 'id' => '<toString(@checkout.completed->id)>']);
        $this->assertResponseContains(
            '@OroCheckoutBundle/Tests/Functional/ApiFrontend/RestJsonApi/responses/get_checkout.yml',
            $response
        );
    }

    public function testTryToGetFromAnotherCustomerUser(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.another_customer_user->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetFromAnotherDepartment(): void
    {
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.another_department_customer_user->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            ['data' => ['type' => 'checkouts']]
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        $this->getReferenceRepository()->setReference('created_checkout', $checkout);
        $this->assertResponseContains(
            '@OroCheckoutBundle/Tests/Functional/ApiFrontend/RestJsonApi/responses/create_checkout_empty.yml',
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

    public function testTryToUpdateFromAnotherCustomerUser(): void
    {
        $checkoutId = $this->getReference('checkout.another_customer_user')->getId();
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
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateFromAnotherDepartment(): void
    {
        $checkoutId = $this->getReference('checkout.another_department_customer_user')->getId();
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
                'title'  => 'access denied exception',
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

    public function testTryToDeleteFromAnotherCustomerUser(): void
    {
        $response = $this->delete(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.another_customer_user->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteFromAnotherDepartment(): void
    {
        $response = $this->delete(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.another_department_customer_user->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
