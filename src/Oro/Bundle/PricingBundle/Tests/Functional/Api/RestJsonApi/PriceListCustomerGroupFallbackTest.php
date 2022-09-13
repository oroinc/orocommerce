<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListCustomerGroupFallbackTest extends AbstractApiPriceListRelationTest
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

        $relation = $this->getEntityManager()
            ->getRepository(PriceListCustomerGroupFallback::class)
            ->findOneBy([
                'website' => $this->getWebsiteForCreateAction(),
                'customerGroup' => $this->getReference('customer_group.group3'),
                'fallback' => 1,
            ]);

        static::assertNotNull($relation);
        static::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $this->getWebsiteForCreateAction()->getId(),
                        'customerGroup' => $this->getReference('customer_group.group3')->getId()
                    ]
                ]
            ]
        );
    }

    public function testDeleteList()
    {
        $relationId1 = $this->getFirstRelation()->getId();
        $relationId2 = $this->getReference(LoadPriceListFallbackSettings::WEBSITE_CUSTOMER_GROUP_FALLBACK_4)->getId();

        $this->cdelete(
            ['entity' => $this->getApiEntityName()],
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
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        'customerGroup' => $this->getReference('customer_group.group1')->getId()
                    ],
                    [
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
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
            ->getRepository(PriceListCustomerGroupFallback::class)
            ->find($relationId);

        static::assertSame(1, $updatedRelation->getFallback());

        static::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'customerGroup' => $this->getReference('customer_group.group1')->getId(),
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                    ]
                ]
            ]
        );
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResourceForFirstRelation('customerGroup', $relation->getCustomerGroup()->getId());
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetRelationshipForFirstRelation(
            'customerGroup',
            CustomerGroup::class,
            $relation->getCustomerGroup()->getId()
        );
    }

    protected function getWebsiteForCreateAction(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }

    /**
     * {@inheritDoc}
     */
    protected function getApiEntityName(): string
    {
        return 'pricelistcustomergroupfallbacks';
    }

    /**
     * {@inheritDoc}
     */
    protected function getAliceFilesFolderName(): string
    {
        return 'price_list_customer_group_fallback';
    }

    /**
     * @return PriceListCustomerGroupFallback
     */
    protected function getFirstRelation()
    {
        return $this->getReference(LoadPriceListFallbackSettings::WEBSITE_CUSTOMER_GROUP_FALLBACK_1);
    }

    /**
     * {@inheritDoc}
     */
    protected function assertFirstRelationMessageSent()
    {
        static::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'customerGroup' => $this->getReference('customer_group.group1')->getId(),
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId()
                    ]
                ]
            ]
        );
    }
}
