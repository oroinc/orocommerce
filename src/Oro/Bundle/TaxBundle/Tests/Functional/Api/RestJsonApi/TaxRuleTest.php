<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxRuleTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTaxBundle/Tests/Functional/Api/DataFixtures/tax_rule.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'taxrules']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'taxrules',
                        'id'            => '<toString(@tax_rule_1->id)>',
                        'attributes'    => [
                            'description' => 'tax rule 1',
                            'createdAt'   => '@tax_rule_1->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_rule_1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'productTaxCode'  => [
                                'data' => ['type' => 'producttaxcodes', 'id' => '<toString(@product_tax_code_1->id)>']
                            ],
                            'customerTaxCode' => [
                                'data' => ['type' => 'customertaxcodes', 'id' => '<toString(@customer_tax_code_1->id)>']
                            ],
                            'tax'             => [
                                'data' => ['type' => 'taxes', 'id' => '<toString(@tax_1->id)>']
                            ],
                            'taxJurisdiction' => [
                                'data' => ['type' => 'taxjurisdictions', 'id' => '<toString(@tax_jurisdiction_1->id)>']
                            ],
                            'organization'    => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'taxrules',
                        'id'            => '<toString(@tax_rule_2->id)>',
                        'attributes'    => [
                            'description' => null,
                            'createdAt'   => '@tax_rule_2->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_rule_2->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'productTaxCode'  => [
                                'data' => ['type' => 'producttaxcodes', 'id' => '<toString(@product_tax_code_2->id)>']
                            ],
                            'customerTaxCode' => [
                                'data' => ['type' => 'customertaxcodes', 'id' => '<toString(@customer_tax_code_2->id)>']
                            ],
                            'tax'             => [
                                'data' => ['type' => 'taxes', 'id' => '<toString(@tax_2->id)>']
                            ],
                            'taxJurisdiction' => [
                                'data' => ['type' => 'taxjurisdictions', 'id' => '<toString(@tax_jurisdiction_2->id)>']
                            ],
                            'organization'    => [
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
            ['entity' => 'taxrules', 'id' => '<toString(@tax_rule_1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'taxrules',
                    'id'            => '<toString(@tax_rule_1->id)>',
                    'attributes'    => [
                        'description' => 'tax rule 1',
                        'createdAt'   => '@tax_rule_1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt'   => '@tax_rule_1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
                        'productTaxCode'  => [
                            'data' => ['type' => 'producttaxcodes', 'id' => '<toString(@product_tax_code_1->id)>']
                        ],
                        'customerTaxCode' => [
                            'data' => ['type' => 'customertaxcodes', 'id' => '<toString(@customer_tax_code_1->id)>']
                        ],
                        'tax'             => [
                            'data' => ['type' => 'taxes', 'id' => '<toString(@tax_1->id)>']
                        ],
                        'taxJurisdiction' => [
                            'data' => ['type' => 'taxjurisdictions', 'id' => '<toString(@tax_jurisdiction_1->id)>']
                        ],
                        'organization'    => [
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
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $this->delete(
            ['entity' => 'taxrules', 'id' => (string)$taxRuleId]
        );

        $taxRule = $this->getEntityManager()->find(TaxRule::class, $taxRuleId);
        self::assertTrue(null === $taxRule);
    }

    public function testDeleteList(): void
    {
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $this->cdelete(
            ['entity' => 'taxrules'],
            ['filter[id]' => (string)$taxRuleId]
        );

        $taxRule = $this->getEntityManager()->find(TaxRule::class, $taxRuleId);
        self::assertTrue(null === $taxRule);
    }

    public function testCreate(): void
    {
        $productTaxCodeId = $this->getReference('product_tax_code_1')->getId();
        $customerTaxCodeId = $this->getReference('customer_tax_code_1')->getId();
        $taxId = $this->getReference('tax_2')->getId();
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_2')->getId();
        $organizationId = $this->getReference('organization')->getId();

        $data = [
            'data' => [
                'type'          => 'taxrules',
                'attributes'    => [
                    'description' => 'new tax rule',
                ],
                'relationships' => [
                    'productTaxCode'  => [
                        'data' => ['type' => 'producttaxcodes', 'id' => (string)$productTaxCodeId]
                    ],
                    'customerTaxCode' => [
                        'data' => ['type' => 'customertaxcodes', 'id' => (string)$customerTaxCodeId]
                    ],
                    'tax'             => [
                        'data' => ['type' => 'taxes', 'id' => (string)$taxId]
                    ],
                    'taxJurisdiction' => [
                        'data' => ['type' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'taxrules'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['organization']['data'] = [
            'type' => 'organizations',
            'id'   => (string)$organizationId
        ];
        $this->assertResponseContains($expectedData, $response);

        $taxRule = $this->getEntityManager()->find(TaxRule::class, $this->getResourceId($response));
        self::assertNotNull($taxRule);
        self::assertEquals('new tax rule', $taxRule->getDescription());
        self::assertEquals($productTaxCodeId, $taxRule->getProductTaxCode()->getId());
        self::assertEquals($customerTaxCodeId, $taxRule->getCustomerTaxCode()->getId());
        self::assertEquals($taxId, $taxRule->getTax()->getId());
        self::assertEquals($taxJurisdictionId, $taxRule->getTaxJurisdiction()->getId());
        self::assertEquals($organizationId, $taxRule->getOrganization()->getId());
    }

    public function testTryToCreateWithoutRequiredFields(): void
    {
        $response = $this->post(
            ['entity' => 'taxrules'],
            ['data' => ['type' => 'taxrules']],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/productTaxCode/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/customerTaxCode/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/tax/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/taxJurisdiction/data']
                ]
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $data = [
            'data' => [
                'type'       => 'taxrules',
                'id'         => (string)$taxRuleId,
                'attributes' => [
                    'description' => 'updated tax code'
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'taxrules', 'id' => (string)$taxRuleId],
            $data
        );

        $taxRule = $this->getEntityManager()->find(TaxRule::class, $taxRuleId);
        self::assertNotNull($taxRule);
        self::assertEquals('updated tax code', $taxRule->getDescription());
    }

    public function testTryToSetProductTaxCodeToNull(): void
    {
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxrules', 'id' => (string)$taxRuleId],
            [
                'data' => [
                    'type'          => 'taxrules',
                    'id'            => (string)$taxRuleId,
                    'relationships' => [
                        'productTaxCode' => ['data' => null]
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
                'source' => ['pointer' => '/data/relationships/productTaxCode/data']
            ],
            $response
        );
    }

    public function testTryToSetCustomerTaxCodeToNull(): void
    {
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxrules', 'id' => (string)$taxRuleId],
            [
                'data' => [
                    'type'          => 'taxrules',
                    'id'            => (string)$taxRuleId,
                    'relationships' => [
                        'customerTaxCode' => ['data' => null]
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
                'source' => ['pointer' => '/data/relationships/customerTaxCode/data']
            ],
            $response
        );
    }

    public function testTryToSetTaxToNull(): void
    {
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxrules', 'id' => (string)$taxRuleId],
            [
                'data' => [
                    'type'          => 'taxrules',
                    'id'            => (string)$taxRuleId,
                    'relationships' => [
                        'tax' => ['data' => null]
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
                'source' => ['pointer' => '/data/relationships/tax/data']
            ],
            $response
        );
    }

    public function testTryToSetTaxJurisdictionToNull(): void
    {
        $taxRuleId = $this->getReference('tax_rule_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxrules', 'id' => (string)$taxRuleId],
            [
                'data' => [
                    'type'          => 'taxrules',
                    'id'            => (string)$taxRuleId,
                    'relationships' => [
                        'taxJurisdiction' => ['data' => null]
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
                'source' => ['pointer' => '/data/relationships/taxJurisdiction/data']
            ],
            $response
        );
    }

    public function testGetSubresourceForOrganization()
    {
        $response = $this->getSubresource(
            ['entity' => 'taxrules', 'id' => '<toString(@tax_rule_1->id)>', 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOrganization()
    {
        $response = $this->getRelationship(
            ['entity' => 'taxrules', 'id' => '<toString(@tax_rule_1->id)>', 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testTryToUpdateRelationshipForOrganization(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'taxrules', 'id' => '<toString(@tax_rule_1->id)>', 'association' => 'organization'],
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
