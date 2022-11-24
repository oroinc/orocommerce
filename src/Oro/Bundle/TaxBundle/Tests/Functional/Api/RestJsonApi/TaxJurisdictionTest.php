<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\ZipCode;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxJurisdictionTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTaxBundle/Tests/Functional/Api/DataFixtures/tax_jurisdiction.yml'
        ]);
    }

    private static function assertZipCode(
        ZipCode $entity,
        ?string $zipCode,
        ?string $zipRangeStart,
        ?string $zipRangeEnd
    ): void {
        self::assertSame($zipCode, $entity->getZipCode(), 'zipCode');
        self::assertSame($zipRangeStart, $entity->getZipRangeStart(), 'zipRangeStart');
        self::assertSame($zipRangeEnd, $entity->getZipRangeEnd(), 'zipRangeEnd');
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'taxjurisdictions']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'taxjurisdictions',
                        'id'            => '<toString(@tax_jurisdiction_1->id)>',
                        'attributes'    => [
                            'code'        => 'TAX_JURISDICTION_1',
                            'description' => 'Tax jurisdiction 1',
                            'regionText'  => null,
                            'zipCodes'    => [
                                ['from' => '90011', 'to' => null],
                                ['from' => '90201', 'to' => '90280']
                            ],
                            'createdAt'   => '@tax_jurisdiction_1->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_jurisdiction_1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'country' => [
                                'data' => [
                                    'type' => 'countries',
                                    'id'   => '<toString(@country_usa->iso2Code)>'
                                ]
                            ],
                            'region'  => [
                                'data' => [
                                    'type' => 'regions',
                                    'id'   => '<toString(@region_usa_california->combinedCode)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'taxjurisdictions',
                        'id'            => '<toString(@tax_jurisdiction_2->id)>',
                        'attributes'    => [
                            'code'        => 'TAX_JURISDICTION_2',
                            'description' => null,
                            'regionText'  => null,
                            'zipCodes'    => [],
                            'createdAt'   => '@tax_jurisdiction_2->createdAt->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt'   => '@tax_jurisdiction_2->updatedAt->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'country' => [
                                'data' => [
                                    'type' => 'countries',
                                    'id'   => '<toString(@country_usa->iso2Code)>'
                                ]
                            ],
                            'region'  => [
                                'data' => [
                                    'type' => 'regions',
                                    'id'   => '<toString(@region_usa_florida->combinedCode)>'
                                ]
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
            ['entity' => 'taxjurisdictions', 'id' => '<toString(@tax_jurisdiction_1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'taxjurisdictions',
                    'id'            => '<toString(@tax_jurisdiction_1->id)>',
                    'attributes'    => [
                        'code'        => 'TAX_JURISDICTION_1',
                        'description' => 'Tax jurisdiction 1',
                        'regionText'  => null,
                        'zipCodes'    => [
                            ['from' => '90011', 'to' => null],
                            ['from' => '90201', 'to' => '90280']
                        ],
                        'createdAt'   => '@tax_jurisdiction_1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt'   => '@tax_jurisdiction_1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
                        'country' => [
                            'data' => [
                                'type' => 'countries',
                                'id'   => '<toString(@country_usa->iso2Code)>'
                            ]
                        ],
                        'region'  => [
                            'data' => [
                                'type' => 'regions',
                                'id'   => '<toString(@region_usa_california->combinedCode)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $this->delete(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertTrue(null === $taxJurisdiction);
    }

    public function testDeleteList(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $this->cdelete(
            ['entity' => 'taxjurisdictions'],
            ['filter[id]' => (string)$taxJurisdictionId]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertTrue(null === $taxJurisdiction);
    }

    public function testCreate(): void
    {
        $countryIso2Code = $this->getReference('country_usa')->getIso2Code();
        $regionCombinedCode = $this->getReference('region_usa_california')->getCombinedCode();

        $data = [
            'data' => [
                'type'          => 'taxjurisdictions',
                'attributes'    => [
                    'code'        => 'NEW_TAX_JURISDICTION',
                    'description' => 'new tax jurisdiction',
                    'regionText'  => null,
                    'zipCodes'    => [
                        ['from' => '90011', 'to' => null],
                        ['from' => '90201', 'to' => '90280']
                    ]
                ],
                'relationships' => [
                    'country' => [
                        'data' => ['type' => 'countries', 'id' => $countryIso2Code]
                    ],
                    'region'  => [
                        'data' => ['type' => 'regions', 'id' => $regionCombinedCode]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'taxjurisdictions'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $this->getResourceId($response));
        self::assertNotNull($taxJurisdiction);
        self::assertEquals('NEW_TAX_JURISDICTION', $taxJurisdiction->getCode());
        self::assertEquals('new tax jurisdiction', $taxJurisdiction->getDescription());
        self::assertNull($taxJurisdiction->getRegionText());
        self::assertEquals($countryIso2Code, $taxJurisdiction->getCountry()->getIso2Code());
        self::assertEquals($regionCombinedCode, $taxJurisdiction->getRegion()->getCombinedCode());
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(2, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], null, '90201', '90280');
    }

    public function testCreateWithoutZipCodes(): void
    {
        $countryIso2Code = $this->getReference('country_usa')->getIso2Code();
        $regionCombinedCode = $this->getReference('region_usa_california')->getCombinedCode();

        $data = [
            'data' => [
                'type'          => 'taxjurisdictions',
                'attributes'    => [
                    'code' => 'NEW_TAX_JURISDICTION',
                ],
                'relationships' => [
                    'country' => [
                        'data' => ['type' => 'countries', 'id' => $countryIso2Code]
                    ],
                    'region'  => [
                        'data' => ['type' => 'regions', 'id' => $regionCombinedCode]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'taxjurisdictions'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $this->getResourceId($response));
        self::assertNotNull($taxJurisdiction);
        self::assertEquals('NEW_TAX_JURISDICTION', $taxJurisdiction->getCode());
        self::assertNull($taxJurisdiction->getDescription());
        self::assertNull($taxJurisdiction->getRegionText());
        self::assertEquals($countryIso2Code, $taxJurisdiction->getCountry()->getIso2Code());
        self::assertEquals($regionCombinedCode, $taxJurisdiction->getRegion()->getCombinedCode());
        self::assertCount(0, $taxJurisdiction->getZipCodes());
    }

    public function testUpdate(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();
        $countryIso2Code = $this->getReference('country_usa')->getIso2Code();
        $regionCombinedCode = $this->getReference('region_usa_california')->getCombinedCode();

        $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'description' => 'updated tax jurisdiction'
                    ]
                ]
            ]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        self::assertEquals('TAX_JURISDICTION_1', $taxJurisdiction->getCode());
        self::assertEquals('updated tax jurisdiction', $taxJurisdiction->getDescription());
        self::assertNull($taxJurisdiction->getRegionText());
        self::assertEquals($countryIso2Code, $taxJurisdiction->getCountry()->getIso2Code());
        self::assertEquals($regionCombinedCode, $taxJurisdiction->getRegion()->getCombinedCode());
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(2, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], null, '90201', '90280');
    }

    public function testUpdateZipCodes(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['from' => '90011', 'to' => null],
                            ['from' => '92503', 'to' => null],
                            ['from' => '90201', 'to' => '90280'],
                            ['from' => '92126', 'to' => '92154']
                        ]
                    ]
                ]
            ]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(4, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], '92503', null, null);
        self::assertZipCode($zipCodes[2], null, '90201', '90280');
        self::assertZipCode($zipCodes[3], null, '92126', '92154');
    }

    public function testRemoveZipCodes(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => []
                    ]
                ]
            ]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        self::assertCount(0, $taxJurisdiction->getZipCodes());
        self::assertCount(
            0,
            $this->getEntityManager()->getRepository(ZipCode::class)->findBy(['taxJurisdiction' => $taxJurisdictionId])
        );
    }

    public function testUpdateZipCodesWhenHasEndRangeButNoStartRange(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['to' => '90280']
                        ]
                    ]
                ]
            ]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(1, $zipCodes);
        self::assertZipCode($zipCodes[0], '90280', null, null);
    }

    public function testUpdateZipCodesWhenZipCodeIsNotNumeric(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['from' => 'test']
                        ]
                    ]
                ]
            ]
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(1, $zipCodes);
        self::assertZipCode($zipCodes[0], 'test', null, null);
    }

    public function testTryToUpdateZipCodesWhenStartRangeIsNotNumeric(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['from' => '92503', 'to' => null],
                            ['from' => 'test', 'to' => '90280'],
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'zip code fields constraint',
                'detail' => 'Ranges are supported only for numeric postal codes',
                'source' => ['pointer' => '/data/attributes/zipCodes/1/from']
            ],
            $response
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(2, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], null, '90201', '90280');
    }

    public function testTryToUpdateZipCodesWhenEndRangeIsNotNumeric(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['from' => '92503', 'to' => null],
                            ['from' => '90201', 'to' => 'test']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'zip code fields constraint',
                'detail' => 'Ranges are supported only for numeric postal codes',
                'source' => ['pointer' => '/data/attributes/zipCodes/1/from']
            ],
            $response
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(2, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], null, '90201', '90280');
    }

    public function testTryToUpdateZipCodesWhenStartAndEndRangesAreNotNumeric(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['from' => '92503', 'to' => null],
                            ['from' => 'test1', 'to' => 'test2']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'zip code fields constraint',
                'detail' => 'Ranges are supported only for numeric postal codes',
                'source' => ['pointer' => '/data/attributes/zipCodes/1/from']
            ],
            $response
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(2, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], null, '90201', '90280');
    }

    public function testTryToUpdateZipCodesWhenEmptyStartAndEndRanges(): void
    {
        $taxJurisdictionId = $this->getReference('tax_jurisdiction_1')->getId();

        $response = $this->patch(
            ['entity' => 'taxjurisdictions', 'id' => (string)$taxJurisdictionId],
            [
                'data' => [
                    'type'       => 'taxjurisdictions',
                    'id'         => (string)$taxJurisdictionId,
                    'attributes' => [
                        'zipCodes' => [
                            ['from' => '92503', 'to' => null],
                            ['from' => '', 'to' => '']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'zip code fields constraint',
                'detail' => 'Zip code can\'t be empty',
                'source' => ['pointer' => '/data/attributes/zipCodes/1/from']
            ],
            $response
        );

        $taxJurisdiction = $this->getEntityManager()->find(TaxJurisdiction::class, $taxJurisdictionId);
        self::assertNotNull($taxJurisdiction);
        $zipCodes = $taxJurisdiction->getZipCodes();
        self::assertCount(2, $zipCodes);
        self::assertZipCode($zipCodes[0], '90011', null, null);
        self::assertZipCode($zipCodes[1], null, '90201', '90280');
    }
}
