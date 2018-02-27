<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PriceListTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
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
            'sort' => 'id',
        ];
        $response = $this->cget(['entity' => 'pricelists'], $parameters);

        $this->assertResponseContains('price_list/get_list.yml', $response);
    }

    public function testCreateScheduleIntersection()
    {
        $routeParameters = self::processTemplateData(['entity' => 'pricelists']);
        $parameters = $this->getRequestData('price_list/create_wrong_schedules.yml');

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'Price list schedule segments should not intersect',
            $response->getContent()
        );
    }

    public function testCreateNoCurrencies()
    {
        $routeParameters = self::processTemplateData(['entity' => 'pricelists']);
        $parameters = $this->getRequestData('price_list/create_no_currencies.yml');

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'This collection should contain 1 element or more. Source: currencies.',
            $response->getContent()
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'pricelists'],
            'price_list/create.yml'
        );

        $priceList = $this->getEntityManager()
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => 'New']);

        static::assertFalse($priceList->isDefault());
        static::assertTrue($priceList->isActive());
        static::assertFalse($priceList->isActual());
        static::assertSame('product.category.id == 1', $priceList->getProductAssignmentRule());
        static::assertArrayContains(['USD'], $priceList->getPriceListCurrencies());
        static::assertArrayContains(['RUB'], $priceList->getPriceListCurrencies());
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
        $this->cleanScheduledRelationMessages();

        $priceListId = $this->getFirstPriceList()->getId();

        $this->patch(
            ['entity' => 'pricelists', 'id' => (string) $priceListId],
            'price_list/update.yml'
        );

        $updatedPriceList = $this->getEntityManager()
            ->getRepository(PriceList::class)
            ->find($priceListId);

        static::assertSame('New Name', $updatedPriceList->getName());
        static::assertFalse($updatedPriceList->isActive());
        static::assertArrayContains(['USD'], $updatedPriceList->getPriceListCurrencies());
        static::assertArrayContains(['EUR'], $updatedPriceList->getPriceListCurrencies());
        static::assertArrayContains(['RUB'], $updatedPriceList->getPriceListCurrencies());

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

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference('US')->getId(),
            ]
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1_1')->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::WEBSITE => $this->getReference('US')->getId(),
            ]
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group1')->getId(),
                PriceListRelationTrigger::WEBSITE => $this->getReference('US')->getId(),
            ]
        );
    }

    public function testDelete()
    {
        $priceListId = $this->getFirstPriceList()->getId();

        $this->delete([
            'entity' => 'pricelists',
            'id' => $priceListId,
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
            'entity' => 'pricelists',
            'id' => $priceList->getId(),
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
            'entity' => 'pricelists',
            'id' => $priceList->getId(),
            'association' => 'priceRules'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'pricerules',
                        'id' => (string)$this->getReference('price_list_1_price_rule_1')->getId()
                    ],
                    [
                        'type' => 'pricerules',
                        'id' => (string)$this->getReference('price_list_1_price_rule_2')->getId()
                    ],
                    [
                        'type' => 'pricerules',
                        'id' => (string)$this->getReference('price_list_1_price_rule_3')->getId()
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
            'entity' => 'pricelists',
            'id' => $entityId,
            'association' => $associationName
        ]);

        $result = json_decode($response->getContent(), true);

        foreach ($result['data'] as $subResource) {
            self::assertTrue(in_array($subResource['id'], $expectedAssociationIds, false));
        }
    }
}
