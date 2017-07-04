<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PriceListWebsiteFallbackTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * @internal
     */
    const ENTITY = 'pricelistwebsitefallbacks';

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
        $response = $this->cget(['entity' => self::ENTITY]);

        $this->assertResponseContains('price_list_website_fallback/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => self::ENTITY]);
        $parameters = $this->getRequestData('price_list_website_fallback/create_duplicate.yml');

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
            'price_list_website_fallback/create.yml'
        );

        $relation = $this->getEntityManager()
            ->getRepository(PriceListWebsiteFallback::class)
            ->findOneBy([
                'website' => $this->getReference(LoadWebsiteData::WEBSITE3),
            ]);

        static::assertSame(1, $relation->getFallback());

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE3)->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledRelationMessages();

        $fallbackId1 = $this->getFirstFallback()->getId();
        $fallbackId2 = $this->getReference(LoadPriceListFallbackSettings::WEBSITE_FALLBACK_2)->getId();

        $this->cdelete(
            ['entity' => self::ENTITY],
            [
                'filter' => [
                    'id' => [$fallbackId1, $fallbackId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListWebsiteFallback::class, $fallbackId1)
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceListWebsiteFallback::class, $fallbackId2)
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
        $fallbackId = $this->getFirstFallback()->getId();

        $response = $this->get([
            'entity' => self::ENTITY,
            'id' => $fallbackId,
        ]);

        $this->assertResponseContains('price_list_website_fallback/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $fallbackId = $this->getFirstFallback()->getId();

        $this->patch(
            ['entity' => self::ENTITY, 'id' => (string) $fallbackId],
            'price_list_website_fallback/update.yml'
        );

        $updatedFallback = $this->getEntityManager()
            ->getRepository(PriceListWebsiteFallback::class)
            ->find($fallbackId);

        static::assertSame(1, $updatedFallback->getFallback());

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

        $fallbackId = $this->getFirstFallback()->getId();

        $this->delete([
            'entity' => self::ENTITY,
            'id' => $fallbackId,
        ]);

        $this->assertNull(
            $this->getEntityManager()->find(PriceListWebsiteFallback::class, $fallbackId)
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
        $fallback = $this->getFirstFallback();

        $response = $this->getSubresource([
            'entity' => self::ENTITY,
            'id' => $fallback->getId(),
            'association' => 'website',
        ]);

        $result = json_decode($response->getContent(), true);

        self::assertEquals($fallback->getWebsite()->getId(), $result['data']['id']);
    }

    public function testGetRelationships()
    {
        $fallback = $this->getFirstFallback();

        $response = $this->getRelationship([
            'entity' => self::ENTITY,
            'id' => $fallback->getId(),
            'association' => 'website',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(Website::class),
                    'id' => (string) $fallback->getWebsite()->getId()
                ]
            ],
            $response
        );
    }

    /**
     * @return PriceListWebsiteFallback
     */
    private function getFirstFallback()
    {
        return $this->getReference(LoadPriceListFallbackSettings::WEBSITE_FALLBACK_1);
    }
}
