<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class QuoteShippingAddressTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'quoteshippingaddresses']);

        $this->assertResponseContains('cget_quote_shipping_address.yml', $response);
    }

    public function testGetListFilterByCountry(): void
    {
        $response = $this->cget(
            ['entity' => 'quoteshippingaddresses'],
            ['filter' => ['country' => ['neq' => '<toString(@country.US->iso2Code)>']]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListFilterByRegion(): void
    {
        $response = $this->cget(
            ['entity' => 'quoteshippingaddresses'],
            ['filter' => ['region' => '<toString(@region.US-IN->combinedCode)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'quoteshippingaddresses', 'id' => '<toString(@sale.quote.1.shipping_address->id)>'],
                    ['type' => 'quoteshippingaddresses', 'id' => '<toString(@sale.quote.6.shipping_address->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByCustomRegion(): void
    {
        $response = $this->cget(
            ['entity' => 'quoteshippingaddresses'],
            ['filter' => ['customRegion' => ['exists' => true]]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'quoteshippingaddresses', 'id' => '<toString(@sale.quote.1.shipping_address->id)>'],
            ['meta' => 'title']
        );

        $this->assertResponseContains('get_quote_shipping_address.yml', $response);
    }

    public function testTryToCreateEmpty(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            ['data' => ['type' => 'quoteshippingaddresses']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/quote/data']
            ],
            $response
        );
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $quoteId = $this->getReference('sale.quote.4')->getId();

        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'relationships' => [
                    'country' => [
                        'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                    ],
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string)$quoteId]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        /** @var QuoteAddress $address */
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null !== $address);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);

        $expectedData = array_merge($data, [
            'data' => [
                'id' => (string)$addressId,
                'attributes' => [
                    'phone' => null,
                    'label' => null,
                    'street' => null,
                    'street2' => null,
                    'city' => null,
                    'postalCode' => null,
                    'organization' => null,
                    'customRegion' => null,
                    'namePrefix' => null,
                    'firstName' => null,
                    'middleName' => null,
                    'lastName' => null,
                    'nameSuffix' => null,
                    'validatedAt' => null,
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z')
                ],
                'relationships' => [
                    'region' => [
                        'data' => null
                    ],
                    'customerAddress' => [
                        'data' => null
                    ],
                    'customerUserAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithoutCountry(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.4->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/country/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuote(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'country' => [
                            'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/quote/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNullQuote(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'country' => [
                            'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                        ],
                        'quote' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/quote/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithQuoteThatAlreadyHasShippingAddress(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'country' => [
                            'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                        ],
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quote shipping address constraint',
                'detail' => 'This quote already has a shipping address.',
                'source' => ['pointer' => '/data/relationships/quote/data']
            ],
            $response
        );
    }

    public function testCreateBasedOnCustomerAddress(): void
    {
        $quoteId = $this->getReference('sale.quote.4')->getId();
        $customerAddressId = $this->getReference('sale.customer.level_1.address_1')->getId();

        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'relationships' => [
                    'customerAddress' => [
                        'data' => ['type' => 'customeraddresses', 'id' => (string)$customerAddressId]
                    ],
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string)$quoteId]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        /** @var QuoteAddress $address */
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null !== $address);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getEntityManager()->find(CustomerAddress::class, $customerAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'id' => (string)$addressId,
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerAddress->getPhone(),
                    'label' => $customerAddress->getLabel(),
                    'street' => $customerAddress->getStreet(),
                    'street2' => $customerAddress->getStreet2(),
                    'city' => $customerAddress->getCity(),
                    'postalCode' => $customerAddress->getPostalCode(),
                    'organization' => $customerAddress->getOrganization(),
                    'customRegion' => $customerAddress->getRegionText(),
                    'namePrefix' => $customerAddress->getNamePrefix(),
                    'firstName' => $customerAddress->getFirstName(),
                    'middleName' => $customerAddress->getMiddleName(),
                    'lastName' => $customerAddress->getLastName(),
                    'nameSuffix' => $customerAddress->getNameSuffix(),
                    'validatedAt' => $customerAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'customerUserAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateBasedOnCustomerUserAddress(): void
    {
        $quoteId = $this->getReference('sale.quote.4')->getId();
        $customerUserAddressId = $this->getReference('sale.other.user@test.com.address_1')->getId();

        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'relationships' => [
                    'customerUserAddress' => [
                        'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
                    ],
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => (string)$quoteId]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        /** @var QuoteAddress $address */
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null !== $address);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getEntityManager()->find(CustomerUserAddress::class, $customerUserAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'id' => (string)$addressId,
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerUserAddress->getPhone(),
                    'label' => $customerUserAddress->getLabel(),
                    'street' => $customerUserAddress->getStreet(),
                    'street2' => $customerUserAddress->getStreet2(),
                    'city' => $customerUserAddress->getCity(),
                    'postalCode' => $customerUserAddress->getPostalCode(),
                    'organization' => $customerUserAddress->getOrganization(),
                    'customRegion' => $customerUserAddress->getRegionText(),
                    'namePrefix' => $customerUserAddress->getNamePrefix(),
                    'firstName' => $customerUserAddress->getFirstName(),
                    'middleName' => $customerUserAddress->getMiddleName(),
                    'lastName' => $customerUserAddress->getLastName(),
                    'nameSuffix' => $customerUserAddress->getNameSuffix(),
                    'validatedAt' => $customerUserAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerUserAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerUserAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'customerAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateBasedOnCustomerAddressWhenNoQuote(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'customerAddress' => [
                            'data' => [
                                'type' => 'customeraddresses',
                                'id' => '<toString(@sale.customer.level_1.address_1->id)>'
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
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/quote/data']
            ],
            $response
        );
    }

    public function testTryToCreateBasedOnCustomerUserAddressWhenNoQuote(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'customerUserAddress' => [
                            'data' => [
                                'type' => 'customeruseraddresses',
                                'id' => '<toString(@sale.other.user@test.com.address_1->id)>'
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
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/quote/data']
            ],
            $response
        );
    }

    public function testTryToCreateBasedOnCustomerAddressAndSomeOtherFieldIsSubmitted(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'country' => [
                            'data' => [
                                'type' => 'countries',
                                'id' => '<toString(@country.US->iso2Code)>'
                            ]
                        ],
                        'customerAddress' => [
                            'data' => [
                                'type' => 'customeraddresses',
                                'id' => '<toString(@sale.customer.level_1.address_1->id)>'
                            ]
                        ],
                        'quote' => [
                            'data' => [
                                'type' => 'quotes',
                                'id' => '<toString(@sale.quote.4->id)>'
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
                'title' => 'quote address constraint',
                'detail' => 'Only address fields, a customer user address or a customer address can be set.'
            ],
            $response
        );
    }

    public function testTryToCreateBasedOnCustomerUserAddressAndSomeOtherFieldIsSubmitted(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'country' => [
                            'data' => [
                                'type' => 'countries',
                                'id' => '<toString(@country.US->iso2Code)>'
                            ]
                        ],
                        'customerUserAddress' => [
                            'data' => [
                                'type' => 'customeruseraddresses',
                                'id' => '<toString(@sale.other.user@test.com.address_1->id)>'
                            ]
                        ],
                        'quote' => [
                            'data' => [
                                'type' => 'quotes',
                                'id' => '<toString(@sale.quote.4->id)>'
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
                'title' => 'quote address constraint',
                'detail' => 'Only address fields, a customer user address or a customer address can be set.'
            ],
            $response
        );
    }

    public function testTryToCreateWhenBothCustomerAddressAndCustomerUserAddressAreSubmitted(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'customerAddress' => [
                            'data' => [
                                'type' => 'customeraddresses',
                                'id' => '<toString(@sale.customer.level_1.address_1->id)>'
                            ]
                        ],
                        'customerUserAddress' => [
                            'data' => [
                                'type' => 'customeruseraddresses',
                                'id' => '<toString(@sale.other.user@test.com.address_1->id)>'
                            ]
                        ],
                        'quote' => [
                            'data' => [
                                'type' => 'quotes',
                                'id' => '<toString(@sale.quote.4->id)>'
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
                'title' => 'quote address constraint',
                'detail' => 'Only address fields, a customer user address or a customer address can be set.'
            ],
            $response
        );
    }

    public function testTryToCreateForQuoteMarkedAsDeleted(): void
    {
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'relationships' => [
                        'country' => [
                            'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                        ],
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.2->id)>']
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
                'detail' => 'The quote marked as deleted cannot be changed.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToChangeQuote(): void
    {
        /** @var Quote $quote1 */
        $quote1 = $this->getReference('sale.quote.1');
        $quote1Id = $quote1->getId();
        $quote1AddressId = $quote1->getShippingAddress()->getId();
        /** @var Quote $quote2 */
        $quote2 = $this->getReference('sale.quote.3');
        $quote2Id = $quote2->getId();
        $quote2AddressId = $quote2->getShippingAddress()->getId();

        $response = $this->patch(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$quote1AddressId],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'id' => (string)$quote1AddressId,
                    'relationships' => [
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => (string)$quote2Id]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'id' => (string)$quote1AddressId,
                    'relationships' => [
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => (string)$quote1Id]
                        ]
                    ]
                ]
            ],
            $response
        );

        $this->getEntityManager()->clear();
        /** @var Quote $quote1 */
        $quote1 = $this->getEntityManager()->find(Quote::class, $quote1Id);
        self::assertEquals($quote1AddressId, $quote1->getShippingAddress()->getId());
        /** @var Quote $quote2 */
        $quote2 = $this->getEntityManager()->find(Quote::class, $quote2Id);
        self::assertEquals($quote2AddressId, $quote2->getShippingAddress()->getId());
    }

    public function testUpdateBasedOnCustomerAddress(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();
        $customerAddressId = $this->getReference('sale.customer.level_1.address_1')->getId();
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'id' => (string)$addressId,
                'relationships' => [
                    'customerAddress' => [
                        'data' => ['type' => 'customeraddresses', 'id' => (string)$customerAddressId]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId],
            $data
        );

        /** @var QuoteAddress $address */
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null !== $address);
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getEntityManager()->find(CustomerAddress::class, $customerAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerAddress->getPhone(),
                    'label' => $customerAddress->getLabel(),
                    'street' => $customerAddress->getStreet(),
                    'street2' => $customerAddress->getStreet2(),
                    'city' => $customerAddress->getCity(),
                    'postalCode' => $customerAddress->getPostalCode(),
                    'organization' => $customerAddress->getOrganization(),
                    'customRegion' => $customerAddress->getRegionText(),
                    'namePrefix' => $customerAddress->getNamePrefix(),
                    'firstName' => $customerAddress->getFirstName(),
                    'middleName' => $customerAddress->getMiddleName(),
                    'lastName' => $customerAddress->getLastName(),
                    'nameSuffix' => $customerAddress->getNameSuffix(),
                    'validatedAt' => $customerAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'quote' => [
                        'data' => [
                            'type' => 'quotes',
                            'id' => (string)$quoteId
                        ]
                    ],
                    'customerUserAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateBasedOnCustomerUserAddress(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();
        $customerUserAddressId = $this->getReference('sale.other.user@test.com.address_1')->getId();
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'id' => (string)$addressId,
                'relationships' => [
                    'customerUserAddress' => [
                        'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId],
            $data
        );

        /** @var QuoteAddress $address */
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null !== $address);
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getEntityManager()->find(CustomerUserAddress::class, $customerUserAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerUserAddress->getPhone(),
                    'label' => $customerUserAddress->getLabel(),
                    'street' => $customerUserAddress->getStreet(),
                    'street2' => $customerUserAddress->getStreet2(),
                    'city' => $customerUserAddress->getCity(),
                    'postalCode' => $customerUserAddress->getPostalCode(),
                    'organization' => $customerUserAddress->getOrganization(),
                    'customRegion' => $customerUserAddress->getRegionText(),
                    'namePrefix' => $customerUserAddress->getNamePrefix(),
                    'firstName' => $customerUserAddress->getFirstName(),
                    'middleName' => $customerUserAddress->getMiddleName(),
                    'lastName' => $customerUserAddress->getLastName(),
                    'nameSuffix' => $customerUserAddress->getNameSuffix(),
                    'validatedAt' => $customerUserAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerUserAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerUserAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'quote' => [
                        'data' => [
                            'type' => 'quotes',
                            'id' => (string)$quoteId
                        ]
                    ],
                    'customerAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $address = new QuoteAddress();
        $address->setCountry($this->getReference('country.US'));
        $quote->setShippingAddress($address);
        $this->getEntityManager()->flush();
        $addressId = $address->getId();

        $response = $this->patch(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'id' => (string)$addressId,
                    'attributes' => [
                        'city' => 'UPDATED'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The quote marked as deleted cannot be changed.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDelete(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $this->delete(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId]
        );

        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null === $address);
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertFalse(null === $quote);
    }

    public function testDeleteList(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $this->cdelete(
            ['entity' => 'quoteshippingaddresses'],
            ['filter' => ['id' => (string)$addressId]]
        );

        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null === $address);
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertFalse(null === $quote);
    }

    public function testGetSubresourceForCountry(): void
    {
        $addressId = $this->getReference('sale.quote.6.shipping_address')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'country']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'countries',
                    'id' => '<toString(@country.US->iso2Code)>',
                    'attributes' => [
                        'name' => '@country.US->name'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCountry(): void
    {
        $addressId = $this->getReference('sale.quote.6.shipping_address')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'country']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'countries',
                    'id' => '<toString(@country.US->iso2Code)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateCountryViaRelationship(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'country'],
            [
                'data' => [
                    'type' => 'countries',
                    'id' => '<toString(@country.US->iso2Code)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForRegion(): void
    {
        $addressId = $this->getReference('sale.quote.6.shipping_address')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'region']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'regions',
                    'id' => '<toString(@region.US-IN->combinedCode)>',
                    'attributes' => [
                        'code' => '@region.US-IN->code'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForRegion(): void
    {
        $addressId = $this->getReference('sale.quote.6.shipping_address')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'region']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'regions',
                    'id' => '<toString(@region.US-IN->combinedCode)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRegionViaRelationship(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'region'],
            [
                'data' => [
                    'type' => 'regions',
                    'id' => '<toString(@region.US-IN->combinedCode)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerAddress(): void
    {
        $addressId = $this->getReference('sale.quote.6.shipping_address')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'customerAddress']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customeraddresses',
                    'id' => '<toString(@sale.customer.level_1.address_2->id)>',
                    'attributes' => [
                        'label' => 'sale.customer.level_1.address_2'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCustomerAddress(): void
    {
        $addressId = $this->getReference('sale.quote.6.shipping_address')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'customerAddress']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customeraddresses',
                    'id' => '<toString(@sale.customer.level_1.address_2->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateCustomerAddressViaRelationship(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'customerAddress'],
            [
                'data' => [
                    'type' => 'customeraddresses',
                    'id' => '<toString(@sale.customer.level_1.address_1->id)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerUserAddress(): void
    {
        $addressId = $this->getReference('sale.quote.5.shipping_address')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'customerUserAddress']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customeruseraddresses',
                    'id' => '<toString(@sale.grzegorz.brzeczyszczykiewicz@example.com.address_1->id)>',
                    'attributes' => [
                        'label' => 'sale.grzegorz.brzeczyszczykiewicz@example.com.address_1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCustomerUserAddress(): void
    {
        $addressId = $this->getReference('sale.quote.5.shipping_address')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'customerUserAddress']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customeruseraddresses',
                    'id' => '<toString(@sale.grzegorz.brzeczyszczykiewicz@example.com.address_1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateCustomerUserAddressViaRelationship(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'customerUserAddress'],
            [
                'data' => [
                    'type' => 'customeruseraddresses',
                    'id' => '<toString(@sale.other.user@test.com.address_1->id)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForQuote(): void
    {
        $addressId = $this->getReference('sale.quote.5.shipping_address')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'quote']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => '<toString(@sale.quote.5->id)>',
                    'attributes' => [
                        'identifier' => '@sale.quote.5->qid'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForQuote(): void
    {
        $addressId = $this->getReference('sale.quote.5.shipping_address')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'quote']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => '<toString(@sale.quote.5->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateQuoteViaRelationship(): void
    {
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId, 'association' => 'quote'],
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => '<toString(@sale.quote.2->id)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
