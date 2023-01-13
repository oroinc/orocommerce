<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListCustomerFallbackTest extends AbstractApiPriceListRelationTest
{
    use MessageQueueExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadPriceListFallbackSettings::class
        ]);
    }

    public function testCreate()
    {
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
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'  => $this->getWebsiteForTest()->getId(),
                        'customer' => $customer->getId()
                    ]
                ]
            ]
        );
    }

    public function testDeleteList()
    {
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

        static::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'  => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        'customer' => $this->getReference('customer.level_1_1')->getId()
                    ],
                    [
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
                        'customer'      => $this->getReference('customer.level_1.2')->getId(),
                        'customerGroup' => $this->getReference('customer_group.group2')->getId()
                    ]
                ]
            ]
        );
    }

    public function testUpdate()
    {
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
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'  => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        'customer' => $this->getReference('customer.level_1_1')->getId()
                    ]
                ]
            ]
        );
    }

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
