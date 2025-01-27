<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChangeCheckoutAddressTest extends FrontendRestJsonApiTestCase
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

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()->find(OrderAddress::class, $addressId);
        self::assertNotNull($address);
        self::assertEquals('Updated Address', $address->getLabel());
    }

    public function testUpdateFromCustomerAddress(): void
    {
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'relationships' => [
                        'customerAddress' => [
                            'data' => [
                                'type' => 'customeraddresses',
                                'id' => '<toString(@customer.address->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                    'attributes' => [
                        'label' => 'Customer Address',
                        'street' => 'Customer Address Street',
                        'street2' => null,
                        'city' => 'Los Angeles',
                        'postalCode' => '90001',
                        'organization' => 'Acme',
                        'customRegion' => null,
                        'namePrefix' => null,
                        'nameSuffix' => null,
                        'firstName' => 'John',
                        'middleName' => null,
                        'lastName' => 'Doe',
                        'createdAt' => '@checkout.in_progress.billing_address->created->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@checkout.in_progress.billing_address->updated->format("Y-m-d\TH:i:s\Z")',
                        'phone' => '123-456'
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
                            'data' => [
                                'type' => 'customeraddresses',
                                'id' => '<toString(@customer.address->id)>'
                            ]
                        ],
                        'customerUserAddress' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateFromCustomerUserAddress(): void
    {
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'relationships' => [
                        'customerUserAddress' => [
                            'data' => [
                                'type' => 'customeruseraddresses',
                                'id' => '<toString(@customer_user.address->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                    'attributes' => [
                        'label' => 'Customer User Address',
                        'street' => 'Customer User Address Street',
                        'street2' => null,
                        'city' => 'Los Angeles',
                        'postalCode' => '90001',
                        'organization' => 'Acme',
                        'customRegion' => null,
                        'namePrefix' => null,
                        'nameSuffix' => null,
                        'firstName' => 'John',
                        'middleName' => null,
                        'lastName' => 'Doe',
                        'createdAt' => '@checkout.in_progress.billing_address->created->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@checkout.in_progress.billing_address->updated->format("Y-m-d\TH:i:s\Z")',
                        'phone' => '123-456'
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
                                'id' => '<toString(@customer_user.address->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateForCompletedCheckout(): void
    {
        $addressId = $this->getReference('checkout.completed.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'attributes' => [
                        'label' => 'Updated Address'
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
        $addressId = $this->getReference('checkout.in_progress.billing_address')->getId();
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $em = $this->getEntityManager();
        $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(true);
        $em->flush();
        try {
            $response = $this->patch(
                ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
                [
                    'data' => [
                        'type' => 'checkoutaddresses',
                        'id' => (string)$addressId,
                        'attributes' => [
                            'label' => 'Updated Address'
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
        $addressId = $this->getReference('checkout.deleted.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'attributes' => [
                        'label' => 'Updated Address'
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

    public function testTryToUpdateFromAnotherDepartment(): void
    {
        $addressId = $this->getReference('checkout.another_department_customer_user.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'attributes' => [
                        'label' => 'Updated Address'
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

    public function testTryToUpdateForOrderAddress(): void
    {
        $addressId = $this->getReference('order.billing_address')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'checkoutaddresses',
                    'id' => (string)$addressId,
                    'attributes' => [
                        'label' => 'Updated Address'
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

    public function testTryToUpdateRelationshipForCountry(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'country'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForRegion(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'region'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForCustomerAddress(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'customerAddress'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForCustomerUserAddress(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutaddresses',
                'id' => '<toString(@checkout.in_progress.billing_address->id)>',
                'association' => 'customerUserAddress'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
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
