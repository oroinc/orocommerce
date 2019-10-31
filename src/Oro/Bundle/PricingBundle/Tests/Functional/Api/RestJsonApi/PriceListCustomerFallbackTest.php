<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListCustomerFallbackTest extends AbstractApiPriceListRelationTest
{
    use MessageQueueTrait;

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

    public function testCreate()
    {
        $this->cleanScheduledRelationMessages();

        $this->post(
            ['entity' => $this->getApiEntityName()],
            'create.yml'
        );

        $customer = $this->getReference('customer.level_1.1');

        $relation = $this->getEntityManager()
            ->getRepository(PriceListCustomerFallback::class)
            ->findOneBy([
                'website' => $this->getWebsiteForTest(),
                'customer' => $customer,
                'fallback' => 0
            ]);

        static::assertNotNull($relation);

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getWebsiteForTest()->getId(),
                PriceListRelationTrigger::ACCOUNT => $customer->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
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
            ['entity' => $this->getApiEntityName()],
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

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->patch(
            ['entity' => $this->getApiEntityName(), 'id' => (string) $relationId],
            'update.yml'
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
            'entity' => $this->getApiEntityName(),
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

        $this->assertGetSubResourceForFirstRelation('customer', $relation->getCustomer()->getId());
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetRelationshipForFirstRelation(
            'customer',
            Customer::class,
            $relation->getCustomer()->getId()
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
    protected function getWebsiteForTest(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }

    /**
     * @inheritDoc
     */
    protected function getApiEntityName(): string
    {
        return 'pricelistcustomerfallbacks';
    }

    /**
     * @inheritDoc
     */
    protected function getAliceFilesFolderName(): string
    {
        return 'price_list_customer_fallback';
    }
}
