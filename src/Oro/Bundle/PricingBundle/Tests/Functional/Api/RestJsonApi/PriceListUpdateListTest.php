<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

/**
 * @dbIsolationPerTest
 */
class PriceListUpdateListTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceListSchedules::class,
            LoadPriceRules::class,
            LoadPriceListRelations::class
        ]);
    }

    private function getPriceListId(string $name): int
    {
        $priceList = $this->getEntityManager()
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => $name]);
        if (null === $priceList) {
            throw new \RuntimeException(sprintf('The price list "%s" was not found.', $name));
        }

        return $priceList->getId();
    }

    private function getPriceRuleLexeme(int $priceListId): ?PriceRuleLexeme
    {
        return $this->getEntityManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findOneBy(['priceList' => $priceListId]);
    }

    public function testCreateEntities()
    {
        $data = [
            'data' => [
                [
                    'type'       => 'pricelists',
                    'attributes' => [
                        'name'                  => 'New Price List 1',
                        'priceListCurrencies'   => ['USD'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => true
                    ]
                ],
                [
                    'type'       => 'pricelists',
                    'attributes' => [
                        'name'                  => 'New Price List 2',
                        'priceListCurrencies'   => ['USD'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => false
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceList::class, $data);

        $response = $this->cget(['entity' => 'pricelists'], ['filter[id][gt]' => '@price_list_6->id']);
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            $expectedData['data'][$key]['id'] = 'new';
        }
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);

        foreach ($responseContent['data'] as $item) {
            self::assertNotNull(
                $this->getPriceRuleLexeme((int)$item['id']),
                sprintf('Lexeme for "%s"', $item['attributes']['name'])
            );
        }
    }

    public function testUpdateEntities()
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $priceList4Id = $this->getReference('price_list_4')->getId();
        $this->processUpdateList(
            PriceList::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'pricelists',
                        'id'         => (string)$priceList1Id,
                        'attributes' => [
                            'name'   => 'Updated Price List 1',
                            'active' => false
                        ]
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'pricelists',
                        'id'         => (string)$priceList4Id,
                        'attributes' => [
                            'name'                  => 'Updated Price List 4',
                            'productAssignmentRule' => 'product.category.id > 0'
                        ]
                    ]
                ]
            ]
        );

        self::assertNotNull($this->getPriceRuleLexeme($priceList1Id));
        self::assertNotNull($this->getPriceRuleLexeme($priceList4Id));

        $response = $this->cget(
            ['entity' => 'pricelists'],
            ['filter' => ['id' => [(string)$priceList1Id, (string)$priceList4Id]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'pricelists',
                        'id'         => (string)$priceList1Id,
                        'attributes' => [
                            'name'   => 'Updated Price List 1',
                            'active' => false
                        ]
                    ],
                    [
                        'type'       => 'pricelists',
                        'id'         => (string)$priceList4Id,
                        'attributes' => [
                            'name'                  => 'Updated Price List 4',
                            'productAssignmentRule' => 'product.category.id > 0'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateAndUpdateEntities()
    {
        $updatedPriceListId = $this->getReference('price_list_4')->getId();
        $this->processUpdateList(
            PriceList::class,
            [
                'data' => [
                    [
                        'type'       => 'pricelists',
                        'attributes' => [
                            'name'                  => 'New Price List 1',
                            'priceListCurrencies'   => ['USD'],
                            'productAssignmentRule' => 'product.category.id == 1'
                        ]
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'pricelists',
                        'id'         => (string)$updatedPriceListId,
                        'attributes' => [
                            'name'                  => 'Updated Price List 4',
                            'productAssignmentRule' => 'product.category.id > 0',
                            'active'                => false
                        ]
                    ]
                ]
            ]
        );

        $newPriceListId = $this->getPriceListId('New Price List 1');
        self::assertNotNull($this->getPriceRuleLexeme($newPriceListId));
        self::assertNotNull($this->getPriceRuleLexeme($updatedPriceListId));

        $response = $this->cget(
            ['entity' => 'pricelists'],
            ['filter' => ['id' => [(string)$newPriceListId, (string)$updatedPriceListId]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'pricelists',
                        'id'         => (string)$updatedPriceListId,
                        'attributes' => [
                            'name'                  => 'Updated Price List 4',
                            'productAssignmentRule' => 'product.category.id > 0',
                            'active'                => false
                        ]
                    ],
                    [
                        'type'       => 'pricelists',
                        'id'         => (string)$newPriceListId,
                        'attributes' => [
                            'name'                  => 'New Price List 1',
                            'priceListCurrencies'   => ['USD'],
                            'productAssignmentRule' => 'product.category.id == 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateEntitiesWithIncludes()
    {
        $data = [
            'data'     => [
                [
                    'type'          => 'pricelists',
                    'attributes'    => [
                        'name'                  => 'New Price List 1',
                        'priceListCurrencies'   => ['USD'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => true
                    ],
                    'relationships' => [
                        'schedules' => ['data' => [['type' => 'pricelistschedules', 'id' => 'schedule1']]]
                    ]
                ],
                [
                    'type'          => 'pricelists',
                    'id'            => 'price_list2',
                    'attributes'    => [
                        'name'                  => 'New Price List 2',
                        'priceListCurrencies'   => ['USD'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => false
                    ],
                    'relationships' => [
                        'schedules' => ['data' => [['type' => 'pricelistschedules', 'id' => 'schedule2']]]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'pricelistschedules',
                    'id'         => 'schedule1',
                    'attributes' => [
                        'activeAt'     => '2017-04-12T14:11:39Z',
                        'deactivateAt' => '2017-04-24T14:11:39Z'
                    ]
                ],
                [
                    'type'       => 'pricelistschedules',
                    'id'         => 'schedule2',
                    'attributes' => [
                        'activeAt'     => '2018-04-12T14:11:39Z',
                        'deactivateAt' => '2018-04-24T14:11:39Z'
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceList::class, $data);

        $response = $this->cget(
            ['entity' => 'pricelists'],
            ['filter[id][gt]' => '@price_list_6->id', 'include' => 'schedules']
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            $expectedData['data'][$key]['id'] = 'new';
            $expectedData['data'][$key]['relationships']['schedules']['data'][0]['id'] = 'new';
        }
        foreach ($expectedData['included'] as $key => $item) {
            $expectedData['included'][$key]['id'] = 'new';
        }
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);

        foreach ($responseContent['data'] as $item) {
            self::assertNotNull(
                $this->getPriceRuleLexeme((int)$item['id']),
                sprintf('Lexeme for "%s"', $item['attributes']['name'])
            );
        }
    }
}
