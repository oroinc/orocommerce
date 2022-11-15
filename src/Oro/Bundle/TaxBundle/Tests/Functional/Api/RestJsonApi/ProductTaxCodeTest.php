<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductTaxCodeTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTaxBundle/Tests/Functional/Api/DataFixtures/product_tax_code.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'producttaxcodes']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'producttaxcodes',
                        'id'            => '<toString(@tax_code1->id)>',
                        'attributes'    => [
                            'code'        => 'TAX_CODE_1',
                            'description' => 'tax code 1',
                            'createdAt'   => '@tax_code1->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_code1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'producttaxcodes',
                        'id'            => '<toString(@tax_code2->id)>',
                        'attributes'    => [
                            'code'        => 'TAX_CODE_2',
                            'description' => null,
                            'createdAt'   => '@tax_code2->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_code2->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
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
            ['entity' => 'producttaxcodes', 'id' => '<toString(@tax_code1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'producttaxcodes',
                    'id'            => '<toString(@tax_code1->id)>',
                    'attributes'    => [
                        'code'        => 'TAX_CODE_1',
                        'description' => 'tax code 1',
                        'createdAt'   => '@tax_code1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt'   => '@tax_code1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
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
            ['entity' => 'producttaxcodes', 'id' => (string)$taxCodeId]
        );

        $tacCode = $this->getEntityManager()->find(ProductTaxCode::class, $taxCodeId);
        self::assertTrue(null === $tacCode);
    }

    public function testDeleteList(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $this->cdelete(
            ['entity' => 'producttaxcodes'],
            ['filter[id]' => (string)$taxCodeId]
        );

        $taxCode = $this->getEntityManager()->find(ProductTaxCode::class, $taxCodeId);
        self::assertTrue(null === $taxCode);
    }

    public function testCreate(): void
    {
        $organizationId = $this->getReference('organization')->getId();

        $data = [
            'data' => [
                'type'       => 'producttaxcodes',
                'attributes' => [
                    'code'        => 'NEW_TAX_CODE',
                    'description' => 'new tax code'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'producttaxcodes'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['organization']['data'] = [
            'type' => 'organizations',
            'id'   => (string)$organizationId
        ];
        $this->assertResponseContains($expectedData, $response);

        $taxCode = $this->getEntityManager()->find(ProductTaxCode::class, $this->getResourceId($response));
        self::assertNotNull($taxCode);
        self::assertEquals('NEW_TAX_CODE', $taxCode->getCode());
        self::assertEquals('new tax code', $taxCode->getDescription());
        self::assertEquals($organizationId, $taxCode->getOrganization()->getId());
    }

    public function testTryToCreateTaxCodeWithoutCode(): void
    {
        $response = $this->post(
            ['entity' => 'producttaxcodes'],
            ['data' => ['type' => 'producttaxcodes']],
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
            ['entity' => 'producttaxcodes'],
            ['data' => ['type' => 'producttaxcodes', 'attributes' => ['code' => 'TAX_CODE_2']]],
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
                'type'       => 'producttaxcodes',
                'id'         => (string)$taxCodeId,
                'attributes' => [
                    'description' => 'updated tax code'
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'producttaxcodes', 'id' => (string)$taxCodeId],
            $data
        );

        $taxCode = $this->getEntityManager()->find(ProductTaxCode::class, $taxCodeId);
        self::assertNotNull($taxCode);
        self::assertEquals('updated tax code', $taxCode->getDescription());
    }

    public function testTryToSetCodeToNull(): void
    {
        $taxCodeId = $this->getReference('tax_code1')->getId();

        $response = $this->patch(
            ['entity' => 'producttaxcodes', 'id' => (string)$taxCodeId],
            [
                'data' => [
                    'type'       => 'producttaxcodes',
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
            ['entity' => 'producttaxcodes', 'id' => (string)$taxCodeId],
            [
                'data' => [
                    'type'       => 'producttaxcodes',
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

    public function testGetSubresourceForOrganization()
    {
        $response = $this->getSubresource(
            ['entity' => 'producttaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOrganization()
    {
        $response = $this->getRelationship(
            ['entity' => 'producttaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testTryToUpdateRelationshipForOrganization(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'producttaxcodes', 'id' => '<toString(@tax_code1->id)>', 'association' => 'organization'],
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
