<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
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
class PriceListCustomerFallbackTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * @internal
     */
    const ENTITY = 'pricelistcustomerfallbacks';

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
                    '@US_customer_1_1_price_list_fallback->id',
                    '@Canada_customer_1_3_price_list_fallback->id',
                ],
            ],
            'sort' => 'id',
        ];

        $response = $this->cget(['entity' => self::ENTITY], $parameters);

        $this->assertResponseContains('price_list_customer_fallback/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => self::ENTITY]);
        $parameters = $this->getRequestData('price_list_customer_fallback/create.yml');

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
            'price_list_customer_fallback/create.yml'
        );

        $relation = $this->getEntityManager()
            ->getRepository(PriceListCustomerFallback::class)
            ->findOneBy([
                'website' => $this->getDefaultWebsite(),
                'customer' => $this->getReference('customer.level_1.2'),
                'fallback' => 0
            ]);

        static::assertNotNull($relation);

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getDefaultWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1.2')->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group2')->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledRelationMessages();

        $relationId1 = $this->getFirstRelation()->getId();
        $relationId2 = $this->getReference(LoadPriceListFallbackSettings::WEBSITE_CUSTOMER_FALLBACK_6)->getId();

        $this->cdelete(
            ['entity' => self::ENTITY],
            [
                'filter' => [
                    'id' => [$relationId1, $relationId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListCustomerFallback::class, $relationId1)
        );
        $this->assertNull(
            $this->getEntityManager()->find(PriceListCustomerFallback::class, $relationId2)
        );

        $this->assertFirstRelationMessageSent();

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1.2')->getId(),
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

        $this->assertResponseContains('price_list_customer_fallback/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->patch(
            ['entity' => self::ENTITY, 'id' => (string) $relationId],
            'price_list_customer_fallback/update.yml'
        );

        $updatedRelation = $this->getEntityManager()
            ->getRepository(PriceListCustomerFallback::class)
            ->find($relationId);

        static::assertSame(1, $updatedRelation->getFallback());

        $this->assertFirstRelationMessageSent();
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
            $this->getEntityManager()->find(PriceListCustomerFallback::class, $relationId)
        );

        $this->assertFirstRelationMessageSent();
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $response = $this->getSubresource([
            'entity' => self::ENTITY,
            'id' => $relation->getId(),
            'association' => 'customer'
        ]);

        $result = json_decode($response->getContent(), true);

        self::assertEquals($relation->getCustomer()->getId(), $result['data']['id']);
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $response = $this->getRelationship([
            'entity' => self::ENTITY,
            'id' => $relation->getId(),
            'association' => 'customer',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(Customer::class),
                    'id' => (string) $relation->getCustomer()->getId()
                ]
            ],
            $response
        );
    }

    /**
     * @return PriceListCustomerFallback
     */
    protected function getFirstRelation()
    {
        return $this->getReference(LoadPriceListFallbackSettings::WEBSITE_CUSTOMER_FALLBACK_1);
    }

    protected function assertFirstRelationMessageSent()
    {
        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1_1')->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }
}
