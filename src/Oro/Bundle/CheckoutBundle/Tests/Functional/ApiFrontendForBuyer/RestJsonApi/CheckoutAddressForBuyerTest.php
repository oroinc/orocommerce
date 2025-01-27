<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAddressForBuyerTest extends FrontendRestJsonApiTestCase
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

    public function testTryToGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutaddresses'], [], [], false);
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.in_progress.billing_address->id)>']
        );
        $this->assertResponseContains(
            '@OroCheckoutBundle/Tests/Functional/ApiFrontend/RestJsonApi/responses/get_checkout_address.yml',
            $response
        );
    }

    public function testTryToGetFromAnotherCustomerUser(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_customer_user.billing_address->id)>'
            ],
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
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>'
            ],
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

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutaddresses'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'Use API resource to create or update a checkout.'
                    . ' A checkout address can be created only together with a checkout.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testUpdate(): void
    {
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutaddresses',
                'id' => (string)$addressId,
                'attributes' => [
                    'label' => 'Updated Address'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateFromAnotherCustomerUser(): void
    {
        $checkoutId = $this->getReference('checkout.another_customer_user.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
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
        $checkoutId = $this->getReference('checkout.another_department_customer_user.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
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
        $response = $this->delete(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.in_progress.billing_address->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutaddresses'],
            ['filter' => ['id' => '<toString(@checkout.in_progress.billing_address->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }
}
