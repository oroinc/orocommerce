<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class PriceListTest extends RestJsonApiTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceListSchedules::class,
            LoadPriceRules::class,
            LoadPriceListRelations::class,
        ]);
    }

    public function testGetList()
    {
        $parameters = [
            'filter' => [
                'id' => ['@price_list_2->id', '@price_list_6->id'],
            ],
            'sort'   => 'id',
        ];
        $response = $this->cget(['entity' => 'pricelists'], $parameters);

        $this->assertResponseContains('price_list/get_list.yml', $response);
    }

    public function testTryToCreateWithScheduleIntersection()
    {
        $response = $this->post(
            ['entity' => 'pricelists'],
            'price_list/create_wrong_schedules.yml',
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'schedule intervals intersection constraint',
                    'detail' => 'Price list schedule segments should not intersect',
                    'source' => ['pointer' => '/data/relationships/schedules/data/0']
                ],
                [
                    'title'  => 'schedule intervals intersection constraint',
                    'detail' => 'Price list schedule segments should not intersect',
                    'source' => ['pointer' => '/data/relationships/schedules/data/1']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutCurrencies()
    {
        $response = $this->post(
            ['entity' => 'pricelists'],
            'price_list/create_no_currencies.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'This collection should contain 1 element or more. Source: currencies.'
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidProductUnitExpression()
    {
        $response = $this->post(
            ['entity' => 'pricelists'],
            'price_list/create_with_invalid_expressions.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'price rule relation expressions constraint',
                'detail' => 'Field "test" is not allowed to be used as "Product Unit"',
                'source' => ['pointer' => '/included/0/attributes/productUnitExpression']
            ],
            $response
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'pricelists'],
            'price_list/create.yml'
        );

        /** @var PriceList $priceList */
        $priceList = $this->getEntityManager()
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => 'New']);

        static::assertFalse($priceList->isDefault());
        static::assertTrue($priceList->isActive());
        static::assertFalse($priceList->isActual());
        static::assertSame('product.category.id == 1', $priceList->getProductAssignmentRule());
        static::assertArrayContains(['USD'], $priceList->getCurrencies());
        static::assertArrayContains(['RUB'], $priceList->getCurrencies());
        static::assertEquals($this->getReference('schedule.5'), $priceList->getSchedules()->first());

        $lexeme = $this->getEntityManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findOneBy(['priceList' => $priceList]);

        static::assertNotNull($lexeme);
    }

    public function testDeleteList()
    {
        $priceListId1 = $this->getFirstPriceList()->getId();
        $priceListId2 = $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId();

        $this->cdelete(
            ['entity' => 'pricelists'],
            [
                'filter' => [
                    'id' => [$priceListId1, $priceListId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceList::class, $priceListId1)
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceList::class, $priceListId2)
        );
    }

    public function testGet()
    {
        $priceListId = $this->getFirstPriceList()->getId();

        $response = $this->get(
            ['entity' => 'pricelists', 'id' => $priceListId]
        );

        $this->assertResponseContains('price_list/get.yml', $response);
    }

    public function testUpdate()
    {
        $priceListId = $this->getFirstPriceList()->getId();

        $this->patch(
            ['entity' => 'pricelists', 'id' => (string)$priceListId],
            'price_list/update.yml'
        );

        /** @var PriceList $updatedPriceList */
        $updatedPriceList = $this->getEntityManager()
            ->getRepository(PriceList::class)
            ->find($priceListId);

        static::assertSame('New Name', $updatedPriceList->getName());
        static::assertFalse($updatedPriceList->isActive());
        static::assertArrayContains(['USD'], $updatedPriceList->getCurrencies());
        static::assertArrayContains(['EUR'], $updatedPriceList->getCurrencies());
        static::assertArrayContains(['RUB'], $updatedPriceList->getCurrencies());

        static::assertCount(1, $updatedPriceList->getSchedules());
        static::assertEquals(
            $this->getReference('schedule.4'),
            $updatedPriceList->getSchedules()->first()
        );

        static::assertEmpty($updatedPriceList->getPriceRules());

        $lexeme = $this->getEntityManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findOneBy(['priceList' => $updatedPriceList]);

        static::assertNotNull($lexeme);

        static::assertMessagesSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                [
                    'website' => $this->getReference('US')->getId()
                ],
                [
                    'website'  => $this->getReference('Canada')->getId(),
                    'customer' => $this->getReference('customer.level_1_1')->getId()
                ]
            ]
        );
    }

    public function testUpdateAsIncludedData()
    {
        $priceList = $this->getFirstPriceList();
        $priceListId = $priceList->getId();

        static::assertTrue($priceList->isActive());

        $this->patch(
            ['entity' => 'pricerules', 'id' => '<toString(@price_list_1_price_rule_1->id)>'],
            [
                'data'     => [
                    'type'          => 'pricerules',
                    'id'            => '<toString(@price_list_1_price_rule_1->id)>',
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceListId]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'pricelists',
                        'id'         => (string)$priceListId,
                        'attributes' => [
                            'name'   => 'Updated Name',
                            'active' => false
                        ]
                    ]
                ]
            ]
        );

        $updatedPriceList = $this->getEntityManager()
            ->getRepository(PriceList::class)
            ->find($priceListId);

        static::assertSame('Updated Name', $updatedPriceList->getName());
        static::assertFalse($updatedPriceList->isActive());

        $lexeme = $this->getEntityManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findOneBy(['priceList' => $updatedPriceList]);

        static::assertNotNull($lexeme);

        static::assertMessagesSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                [
                    'website' => $this->getReference('US')->getId()
                ],
                [
                    'website'  => $this->getReference('Canada')->getId(),
                    'customer' => $this->getReference('customer.level_1_1')->getId()
                ]
            ]
        );
    }

    public function testDelete()
    {
        $priceListId = $this->getFirstPriceList()->getId();

        $this->delete([
            'entity' => 'pricelists',
            'id'     => $priceListId,
        ]);

        $this->assertNull(
            $this->getEntityManager()->find(PriceList::class, $priceListId)
        );
    }

    public function testGetSubResources()
    {
        $priceList = $this->getFirstPriceList();

        $this->assertGetSubResource(
            $priceList->getId(),
            'schedules',
            [
                $this->getReference('schedule.1')->getId(),
                $this->getReference('schedule.2')->getId(),
                $this->getReference('schedule.3')->getId(),
            ]
        );

        $this->assertGetSubResource(
            $priceList->getId(),
            'priceRules',
            [
                $this->getReference('price_list_1_price_rule_1')->getId(),
                $this->getReference('price_list_1_price_rule_2')->getId(),
                $this->getReference('price_list_1_price_rule_3')->getId(),
            ]
        );
    }

    public function testGetRelationships()
    {
        $priceList = $this->getFirstPriceList();

        $response = $this->getRelationship([
            'entity'      => 'pricelists',
            'id'          => $priceList->getId(),
            'association' => 'schedules'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'pricelistschedules', 'id' => (string)$this->getReference('schedule.1')->getId()],
                    ['type' => 'pricelistschedules', 'id' => (string)$this->getReference('schedule.2')->getId()],
                    ['type' => 'pricelistschedules', 'id' => (string)$this->getReference('schedule.3')->getId()],
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'pricelists',
            'id'          => $priceList->getId(),
            'association' => 'priceRules'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'pricerules',
                        'id'   => (string)$this->getReference('price_list_1_price_rule_1')->getId()
                    ],
                    [
                        'type' => 'pricerules',
                        'id'   => (string)$this->getReference('price_list_1_price_rule_2')->getId()
                    ],
                    [
                        'type' => 'pricerules',
                        'id'   => (string)$this->getReference('price_list_1_price_rule_3')->getId()
                    ],
                ]
            ],
            $response
        );
    }

    /**
     * @return PriceList
     */
    private function getFirstPriceList()
    {
        return $this->getReference(LoadPriceLists::PRICE_LIST_1);
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param int[]  $expectedAssociationIds
     */
    private function assertGetSubResource(
        int $entityId,
        string $associationName,
        array $expectedAssociationIds
    ) {
        $response = $this->getSubresource([
            'entity'      => 'pricelists',
            'id'          => $entityId,
            'association' => $associationName
        ]);

        $result = json_decode($response->getContent(), true);

        foreach ($result['data'] as $subResource) {
            self::assertTrue(in_array($subResource['id'], $expectedAssociationIds, false));
        }
    }
}
