<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerTaxCodeTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTaxBundle/Tests/Functional/Api/DataFixtures/customer_tax_code.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'customertaxcodes']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'customertaxcodes',
                        'id'            => '<toString(@tax_code1->id)>',
                        'attributes'    => [
                            'code'        => 'TAX_CODE_1',
                            'description' => 'tax code 1',
                            'createdAt'   => '@tax_code1->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_code1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'customertaxcodes',
                        'id'            => '<toString(@tax_code2->id)>',
                        'attributes'    => [
                            'code'        => 'TAX_CODE_2',
                            'description' => null,
                            'createdAt'   => '@tax_code2->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_code2->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'customertaxcodes',
                    'id'            => '<toString(@tax_code1->id)>',
                    'attributes'    => [
                        'code'        => 'TAX_CODE_1',
                        'description' => 'tax code 1',
                        'createdAt'   => '@tax_code1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt'   => '@tax_code1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $this->delete(
            ['entity' => 'customertaxcodes', 'id' => (string)$taxCodeId]
        );

        $tacCode = $this->getEntityManager()->find(CustomerTaxCode::class, $taxCodeId);
        self::assertTrue(null === $tacCode);
    }

    public function testDeleteList(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $this->cdelete(
            ['entity' => 'customertaxcodes'],
            ['filter[id]' => (string)$taxCodeId]
        );

        $taxCode = $this->getEntityManager()->find(CustomerTaxCode::class, $taxCodeId);
        self::assertTrue(null === $taxCode);
    }

    public function testCreate(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();

        $data = [
            'data' => [
                'type'       => 'customertaxcodes',
                'attributes' => [
                    'code'        => 'NEW_TAX_CODE',
                    'description' => 'new tax code'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'customertaxcodes'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id'   => (string)$userId
        ];
        $expectedData['data']['relationships']['organization']['data'] = [
            'type' => 'organizations',
            'id'   => (string)$organizationId
        ];
        $this->assertResponseContains($expectedData, $response);

        $taxCode = $this->getEntityManager()->find(CustomerTaxCode::class, $this->getResourceId($response));
        self::assertNotNull($taxCode);
        self::assertEquals('NEW_TAX_CODE', $taxCode->getCode());
        self::assertEquals('new tax code', $taxCode->getDescription());
        self::assertEquals($userId, $taxCode->getOwner()->getId());
        self::assertEquals($organizationId, $taxCode->getOrganization()->getId());
    }

    public function testTryToCreateTaxCodeWithoutCode(): void
    {
        $response = $this->post(
            ['entity' => 'customertaxcodes'],
            ['data' => ['type' => 'customertaxcodes']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/code']
            ],
            $response
        );
    }

    public function testTryToCreateTaxCodeWithDuplicateCode(): void
    {
        $response = $this->post(
            ['entity' => 'customertaxcodes'],
            ['data' => ['type' => 'customertaxcodes', 'attributes' => ['code' => 'TAX_CODE_2']]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unique tax code constraint',
                'detail' => 'This value is already used.',
                'source' => ['pointer' => '/data/attributes/code']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $data = [
            'data' => [
                'type'       => 'customertaxcodes',
                'id'         => (string)$taxCodeId,
                'attributes' => [
                    'description' => 'updated tax code'
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'customertaxcodes', 'id' => (string)$taxCodeId],
            $data
        );

        $taxCode = $this->getEntityManager()->find(CustomerTaxCode::class, $taxCodeId);
        self::assertNotNull($taxCode);
        self::assertEquals('updated tax code', $taxCode->getDescription());
    }

    public function testTryToSetCodeToNull(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $response = $this->patch(
            ['entity' => 'customertaxcodes', 'id' => (string)$taxCodeId],
            [
                'data' => [
                    'type'       => 'customertaxcodes',
                    'id'         => (string)$taxCodeId,
                    'attributes' => [
                        'code' => null
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/code']
            ],
            $response
        );
    }

    public function testTryToSetDuplicateCode(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $response = $this->patch(
            ['entity' => 'customertaxcodes', 'id' => (string)$taxCodeId],
            [
                'data' => [
                    'type'       => 'customertaxcodes',
                    'id'         => (string)$taxCodeId,
                    'attributes' => [
                        'code' => 'TAX_CODE_2'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unique tax code constraint',
                'detail' => 'This value is already used.',
                'source' => ['pointer' => '/data/attributes/code']
            ],
            $response
        );
    }

    public function testGetSubresourceForOwner()
    {
        $response = $this->getSubresource(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'owner']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'users', 'id' => '<toString(@user->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOwner()
    {
        $response = $this->getRelationship(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'owner']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'users', 'id' => '<toString(@user->id)>']],
            $response
        );
    }

    public function testTryToUpdateRelationshipForOwner(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'owner'],
            ['data' => ['type' => 'users', 'id' => '<toString(@user->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForOrganization()
    {
        $response = $this->getSubresource(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOrganization()
    {
        $response = $this->getRelationship(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testTryToUpdateRelationshipForOrganization(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'customertaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'organization'],
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
