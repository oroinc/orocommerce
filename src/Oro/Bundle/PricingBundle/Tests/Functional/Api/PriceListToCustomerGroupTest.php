<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PriceListToCustomerGroupTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * @internal
     */
    const ENTITY = 'pricelisttocustomergroups';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadPriceListRelations::class
        ]);
    }

    public function testGetList()
    {
        $parameters = [
            'filter' => [
                'id' => [
                    '@price_list_6_US_customer_group1->id',
                    '@price_list_1_US_customer_group1->id',
                ],
            ],
            'sort' => 'id',
        ];

        $response = $this->cget(['entity' => self::ENTITY], $parameters);

        $this->assertResponseContains('price_list_to_customer_group/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => self::ENTITY]);
        $parameters = $this->getRequestData('price_list_to_customer_group/create.yml');

        $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', $routeParameters),
            $parameters
        );

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', $routeParameters),
            $parameters
        );

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'unique entity constraint',
            $response->getContent()
        );
    }

    public function testCreate()
    {
        $this->cleanScheduledRelationMessages();

        $this->post(
            ['entity' => self::ENTITY],
            'price_list_to_customer_group/create.yml'
        );

        $relation = $this->getEntityManager()
            ->getRepository(PriceListToCustomerGroup::class)
            ->findOneBy([
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_6),
                'website' => $this->getDefaultWebsite(),
                'customerGroup' => $this->getReference('customer_group.group1'),
            ]);

        static::assertSame(21, $relation->getSortOrder());
        static::assertTrue($relation->isMergeAllowed());

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getDefaultWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group1')->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledRelationMessages();

        $relationId1 = $this->getFirstRelation()->getId();
        $relationId2 = $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_GROUP_5)->getId();

        $this->cdelete(
            ['entity' => self::ENTITY],
            [
                'filter' => [
                    'id' => [$relationId1, $relationId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListToCustomerGroup::class, $relationId1)
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListToCustomerGroup::class, $relationId2)
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group1')->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group3')->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testGet()
    {
        $relationId = $this->getFirstRelation()->getId();

        $response = $this->get([
            'entity' => self::ENTITY,
            'id' => $relationId,
        ]);

        $this->assertResponseContains('price_list_to_customer_group/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->patch(
            ['entity' => self::ENTITY, 'id' => (string) $relationId],
            'price_list_to_customer_group/update.yml'
        );

        $updatedRelation = $this->getEntityManager()
            ->getRepository(PriceListToCustomerGroup::class)
            ->find($relationId);

        static::assertSame(25, $updatedRelation->getSortOrder());
        static::assertTrue($updatedRelation->isMergeAllowed());

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group1')->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDelete()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->delete([
            'entity' => self::ENTITY,
            'id' => $relationId,
        ]);

        $this->assertNull(
            $this->getEntityManager()->find(PriceListToCustomerGroup::class, $relationId)
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group1')->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResource($relation->getId(), 'priceList', $relation->getPriceList()->getId());
        $this->assertGetSubResource($relation->getId(), 'customerGroup', $relation->getCustomerGroup()->getId());
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $response = $this->getRelationship([
            'entity' => self::ENTITY,
            'id' => $relation->getId(),
            'association' => 'priceList',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(PriceList::class),
                    'id' => (string) $relation->getPriceList()->getId()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity' => self::ENTITY,
            'id' => $relation->getId(),
            'association' => 'customerGroup',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(CustomerGroup::class),
                    'id' => (string) $relation->getCustomerGroup()->getId()
                ]
            ],
            $response
        );
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $associationId
     */
    private function assertGetSubResource($entityId, $associationName, $associationId)
    {
        $response = $this->getSubresource(
            ['entity' => self::ENTITY, 'id' => $entityId, 'association' => $associationName]
        );

        $result = json_decode($response->getContent(), true);

        self::assertEquals($associationId, $result['data']['id']);
    }

    /**
     * @return PriceListToCustomerGroup
     */
    private function getFirstRelation(): PriceListToCustomerGroup
    {
        return $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_GROUP_1);
    }

    /**
     * @return Website
     */
    private function getDefaultWebsite(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }
}
