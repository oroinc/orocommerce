<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendSubresources\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAvailableBillingAddressTest extends FrontendRestJsonApiTestCase
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

    private function getAutoCreatedCustomerUserBillingAddress(): CustomerUserAddress
    {
        $addresses = $this->getEntityManager()->getRepository(CustomerUserAddress::class)->getAddressesByType(
            $this->getReference('customer_user'),
            'billing',
            self::getContainer()->get('oro_security.acl_helper')
        );
        $foundAddress = null;
        foreach ($addresses as $address) {
            if ($this->getReference('customer_user.address')->getId() !== $address->getId()) {
                $foundAddress = $address;
                break;
            }
        }

        return $foundAddress;
    }

    public function testCheckoutGetSubresource(): void
    {
        $response = $this->getSubresource([
            'entity' => 'checkouts',
            'id' => '<toString(@checkout.open->id)>',
            'association' => 'availableBillingAddresses'
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'a_' . $this->getReference('customer.address')->getId(),
                        'attributes' => [
                            'group' => 'Global Address Book',
                            'title' => 'John Doe, Acme, Customer Address Street, LOS ANGELES CA US 90001, 123-456'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeraddresses',
                                    'id' => (string)$this->getReference('customer.address')->getId()
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getReference('customer_user.address')->getId(),
                        'attributes' => [
                            'group' => 'My address book',
                            'title' => 'John Doe, Acme, Customer User Address Street, LOS ANGELES CA US 90001, 123-456'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeruseraddresses',
                                    'id' => (string)$this->getReference('customer_user.address')->getId()
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getAutoCreatedCustomerUserBillingAddress()->getId(),
                        'attributes' => [
                            'group' => 'My address book',
                            'title' => 'John Doe, 1215 Caldwell Road, ROCHESTER CA US 14608'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeruseraddresses',
                                    'id' => (string)$this->getAutoCreatedCustomerUserBillingAddress()->getId()
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testCheckoutGetSubresourceWithFieldsFilter(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            ['fields[checkoutavailableaddresses]' => 'group']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'a_' . $this->getReference('customer.address')->getId(),
                        'attributes' => ['group' => 'Global Address Book']
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getReference('customer_user.address')->getId(),
                        'attributes' => ['group' => 'My address book']
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getAutoCreatedCustomerUserBillingAddress()->getId(),
                        'attributes' => ['group' => 'My address book']
                    ]
                ]
            ],
            $response,
            true
        );
        $responseContent = self::jsonToArray($response->getContent());
        foreach ($responseContent['data'] as $i => $item) {
            self::assertCount(1, $item['attributes'], 'Item #' . $i);
            self::assertArrayNotHasKey('relationships', $item, 'Item #' . $i);
        }
        self::assertArrayNotHasKey('included', $responseContent);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCheckoutGetSubresourceWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            ['include' => 'address']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'a_' . $this->getReference('customer.address')->getId(),
                        'attributes' => [
                            'group' => 'Global Address Book',
                            'title' => 'John Doe, Acme, Customer Address Street, LOS ANGELES CA US 90001, 123-456'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeraddresses',
                                    'id' => (string)$this->getReference('customer.address')->getId()
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getReference('customer_user.address')->getId(),
                        'attributes' => [
                            'group' => 'My address book',
                            'title' => 'John Doe, Acme, Customer User Address Street, LOS ANGELES CA US 90001, 123-456'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeruseraddresses',
                                    'id' => (string)$this->getReference('customer_user.address')->getId()
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getAutoCreatedCustomerUserBillingAddress()->getId(),
                        'attributes' => [
                            'group' => 'My address book',
                            'title' => 'John Doe, 1215 Caldwell Road, ROCHESTER CA US 14608'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeruseraddresses',
                                    'id' => (string)$this->getAutoCreatedCustomerUserBillingAddress()->getId()
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'customeraddresses',
                        'id' => (string)$this->getReference('customer.address')->getId(),
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
                            'lastName' => 'Doe'
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
                            ]
                        ]
                    ],
                    [
                        'type' => 'customeruseraddresses',
                        'id' => (string)$this->getReference('customer_user.address')->getId(),
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
                            'lastName' => 'Doe'
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
                            ]
                        ]
                    ],
                    [
                        'type' => 'customeruseraddresses',
                        'id' => (string)$this->getAutoCreatedCustomerUserBillingAddress()->getId(),
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
                            'lastName' => 'Doe'
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
                            ]
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testCheckoutGetSubresourceWithIncludeAndFieldsFilters(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            [
                'include' => 'address',
                'fields[customeraddresses]' => 'label',
                'fields[customeruseraddresses]' => 'label'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'a_' . $this->getReference('customer.address')->getId(),
                        'attributes' => [
                            'group' => 'Global Address Book',
                            'title' => 'John Doe, Acme, Customer Address Street, LOS ANGELES CA US 90001, 123-456'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeraddresses',
                                    'id' => (string)$this->getReference('customer.address')->getId()
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getReference('customer_user.address')->getId(),
                        'attributes' => [
                            'group' => 'My address book',
                            'title' => 'John Doe, Acme, Customer User Address Street, LOS ANGELES CA US 90001, 123-456'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeruseraddresses',
                                    'id' => (string)$this->getReference('customer_user.address')->getId()
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'checkoutavailableaddresses',
                        'id' => 'au_' . $this->getAutoCreatedCustomerUserBillingAddress()->getId(),
                        'attributes' => [
                            'group' => 'My address book',
                            'title' => 'John Doe, 1215 Caldwell Road, ROCHESTER CA US 14608'
                        ],
                        'relationships' => [
                            'address' => [
                                'data' => [
                                    'type' => 'customeruseraddresses',
                                    'id' => (string)$this->getAutoCreatedCustomerUserBillingAddress()->getId()
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'customeraddresses',
                        'id' => (string)$this->getReference('customer.address')->getId(),
                        'attributes' => [
                            'label' => 'Customer Address'
                        ]
                    ],
                    [
                        'type' => 'customeruseraddresses',
                        'id' => (string)$this->getReference('customer_user.address')->getId(),
                        'attributes' => [
                            'label' => 'Customer User Address'
                        ]
                    ],
                    [
                        'type' => 'customeruseraddresses',
                        'id' => (string)$this->getAutoCreatedCustomerUserBillingAddress()->getId(),
                        'attributes' => [
                            'label' => null
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
        $responseContent = self::jsonToArray($response->getContent());
        foreach ($responseContent['included'] as $i => $item) {
            self::assertCount(1, $item['attributes'], 'Item #' . $i);
            self::assertArrayNotHasKey('relationships', $item, 'Item #' . $i);
        }
    }

    public function testCheckoutGetSubresourceForNotExistingCheckout(): void
    {
        $response = $this->getSubresource([
            'entity' => 'checkouts',
            'id' => '999999',
            'association' => 'availableBillingAddresses'
        ]);
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testCheckoutGetSubresourceForDeletedCheckout(): void
    {
        $response = $this->getSubresource([
            'entity' => 'checkouts',
            'id' => '<toString(@checkout.deleted->id)>',
            'association' => 'availableBillingAddresses'
        ]);
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToCheckoutGetRelationship(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCheckoutUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCheckoutAddRelationship(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCheckoutDeleteRelationship(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableBillingAddresses'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutavailableaddresses'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutavailableaddresses', 'id' => 'a_1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutavailableaddresses'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'checkoutavailableaddresses', 'id' => 'a_1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutavailableaddresses', 'id' => 'a_1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutavailableaddresses'],
            ['filter' => ['checkout' => '@checkout.open->id']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
