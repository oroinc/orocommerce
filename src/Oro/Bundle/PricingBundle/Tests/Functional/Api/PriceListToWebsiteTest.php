<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListToWebsiteTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * @internal
     */
    const ENTITY = 'pricelisttowebsites';

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
        $response = $this->cget(['entity' => self::ENTITY]);

        $this->assertResponseContains('price_list_to_website/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => self::ENTITY]);
        $parameters = $this->getRequestData('price_list_to_website/create_duplicate.yml');

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
            'price_list_to_website/create.yml'
        );

        $relation = $this->getEntityManager()
            ->getRepository(PriceListToWebsite::class)
            ->findOneBy([
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_4),
                'website' => $this->getReference(LoadWebsiteData::WEBSITE1),
            ]);

        static::assertSame(12, $relation->getSortOrder());
        static::assertFalse($relation->isMergeAllowed());

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledRelationMessages();

        $relationId1 = $this->getFirstRelation()->getId();
        $relationId2 = $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_WEBSITE_4)->getId();

        $this->cdelete(
            ['entity' => self::ENTITY],
            [
                'filter' => [
                    'id' => [$relationId1, $relationId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListToWebsite::class, $relationId1)
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListToWebsite::class, $relationId2)
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
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

        $this->assertResponseContains('price_list_to_website/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->patch(
            ['entity' => self::ENTITY, 'id' => (string) $relationId],
            'price_list_to_website/update.yml'
        );

        $updatedRelation = $this->getEntityManager()
            ->getRepository(PriceListToWebsite::class)
            ->find($relationId);

        static::assertSame(87, $updatedRelation->getSortOrder());
        static::assertTrue($updatedRelation->isMergeAllowed());

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
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
            $this->getEntityManager()->find(PriceListToWebsite::class, $relationId)
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResource($relation->getId(), 'priceList', $relation->getPriceList()->getId());
        $this->assertGetSubResource($relation->getId(), 'website', $relation->getWebsite()->getId());
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
                    'id' => (string)$relation->getPriceList()->getId()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity' => self::ENTITY,
            'id' => $relation->getId(),
            'association' => 'website',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(Website::class),
                    'id' => (string)$relation->getWebsite()->getId()
                ]
            ],
            $response
        );
    }

    /**
     * @return PriceListToWebsite
     */
    private function getFirstRelation()
    {
        return $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_WEBSITE_1);
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
}
