<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListCustomerGroupFallbackTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * @internal
     */
    const ENTITY = 'pricelistcustomergroupfallbacks';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadPriceListFallbackSettings::class
        ]);
    }

    public function testGetList()
    {
        $parameters = [
            'filter' => [
                'id' => [
                        '@US_customer_group1_price_list_fallback->id',
                        '@Canada_customer_group2_price_list_fallback->id',
                    ],
            ],
            'sort' => 'id',
        ];

        $response = $this->cget(['entity' => self::ENTITY], $parameters);

        $this->assertResponseContains('price_list_customer_group_fallback/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => self::ENTITY]);
        $parameters = $this->getRequestData('price_list_customer_group_fallback/create.yml');

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
            'price_list_customer_group_fallback/create.yml'
        );

        $relation = $this->getEntityManager()
            ->getRepository(PriceListCustomerGroupFallback::class)
            ->findOneBy([
                'website' => $this->getDefaultWebsite(),
                'customerGroup' => $this->getReference('customer_group.group1'),
            ]);

        static::assertSame(1, $relation->getFallback());

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
        $relationId2 = $this->getReference(LoadPriceListFallbackSettings::WEBSITE_CUSTOMER_GROUP_FALLBACK_4)->getId();

        $this->cdelete(
            ['entity' => self::ENTITY],
            [
                'filter' => [
                    'id' => [$relationId1, $relationId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListCustomerGroupFallback::class, $relationId1)
        );
        $this->assertNull(
            $this->getEntityManager()->find(PriceListCustomerGroupFallback::class, $relationId2)
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
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group2')->getId(),
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

        $this->assertResponseContains('price_list_customer_group_fallback/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->patch(
            ['entity' => self::ENTITY, 'id' => (string) $relationId],
            'price_list_customer_group_fallback/update.yml'
        );

        $updatedRelation = $this->getEntityManager()
            ->getRepository(PriceListCustomerGroupFallback::class)
            ->find($relationId);

        static::assertSame(1, $updatedRelation->getFallback());

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
            $this->getEntityManager()->find(PriceListCustomerGroupFallback::class, $relationId)
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

        $response = $this->getSubresource([
            'entity' => self::ENTITY,
            'id' => $relation->getId(),
            'association' => 'customerGroup'
        ]);

        $result = json_decode($response->getContent(), true);

        self::assertEquals($relation->getCustomerGroup()->getId(), $result['data']['id']);
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

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
     * @return PriceListCustomerGroupFallback
     */
    private function getFirstRelation(): PriceListCustomerGroupFallback
    {
        return $this->getReference(LoadPriceListFallbackSettings::WEBSITE_CUSTOMER_GROUP_FALLBACK_1);
    }

    /**
     * @return Website
     */
    private function getDefaultWebsite(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }
}
