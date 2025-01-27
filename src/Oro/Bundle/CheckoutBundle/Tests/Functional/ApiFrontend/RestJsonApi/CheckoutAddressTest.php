<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CheckoutAddressTest extends FrontendRestJsonApiTestCase
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
        $this->assertResponseContains('get_checkout_address.yml', $response);
    }

    public function testGetForCompletedCheckout(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.completed.billing_address->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => '<toString(@checkout.completed.billing_address->id)>',
                    'attributes' => [
                        'label' => null,
                        'street' => '1215 Caldwell Road',
                        'street2' => null,
                        'city' => 'Rochester',
                        'postalCode' => '14608',
                        'organization' => null,
                        'customRegion' => null,
                        'namePrefix' => null,
                        'nameSuffix' => null,
                        'firstName' => 'John',
                        'middleName' => null,
                        'lastName' => 'Doe',
                        'createdAt' => '@checkout.completed.billing_address->created->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@checkout.completed.billing_address->updated->format("Y-m-d\TH:i:s\Z")',
                        'phone' => null
                    ],
                    'relationships' => [
                        'country' => [
                            'data' => [
                                'type' => 'countries',
                                'id' => '<toString(@country_usa->iso2Code)>'
                            ]
                        ],
                        'region' => [
                            'data' => [
                                'type' => 'regions',
                                'id' => '<toString(@region_usa_california->combinedCode)>'
                            ]
                        ],
                        'customerAddress' => [
                            'data' => null
                        ],
                        'customerUserAddress' => [
                            'data' => [
                                'type' => 'customeruseraddresses',
                                'id' => '<toString(@checkout.completed.billing_address.customer_user_address->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForPaymentInProgressCheckout(): void
    {
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $em = $this->getEntityManager();
        $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(true);
        $em->flush();
        try {
            $response = $this->get(
                ['entity' => 'checkoutaddresses', 'id' => (string)$addressId]
            );
            $this->assertResponseContains(
                [
                    'data' => [
                        'type' => 'checkoutaddresses',
                        'id' => (string)$addressId,
                        'attributes' => [
                            'firstName' => 'John',
                            'lastName' => 'Doe'
                        ]
                    ]
                ],
                $response
            );
        } finally {
            $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(false);
            $em->flush();
        }
    }

    public function testTryToGetForDeletedCheckout(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.deleted.billing_address->id)>'],
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
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForOrderAddress(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@order.billing_address->id)>'],
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

    public function testGetSubresourceForCountry(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'countries', 'id' => '<toString(@country_usa->iso2Code)>']],
            $response
        );
    }

    public function testGetRelationshipForCountry(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'countries', 'id' => '<toString(@country_usa->iso2Code)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForCountryFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForCountryFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetSubresourceForRegion(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'regions', 'id' => '<toString(@region_usa_california->combinedCode)>']],
            $response
        );
    }

    public function testGetRelationshipForRegion(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'region'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'regions', 'id' => '<toString(@region_usa_california->combinedCode)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForRegionFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForRegionFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetSubresourceForCustomerAddress(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => null],
            $response
        );
    }

    public function testGetRelationshipForCustomerAddress(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'customerAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => null],
            $response
        );
    }

    public function testTryToGetSubresourceForCustomerAddressFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForCustomerAddressFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetSubresourceForCustomerUserAddress(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => null],
            $response
        );
    }

    public function testGetRelationshipForCustomerUserAddress(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'customerUserAddress'
            ]
        );
        $this->assertResponseContains(
            ['data' => null],
            $response
        );
    }

    public function testTryToGetSubresourceForCustomerUserAddressFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForCustomerUserAddressFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.another_department_customer_user.billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
