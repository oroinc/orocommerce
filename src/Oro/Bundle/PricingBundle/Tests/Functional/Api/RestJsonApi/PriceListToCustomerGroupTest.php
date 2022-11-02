<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListToCustomerGroupTest extends AbstractApiPriceListRelationTest
{
    use MessageQueueExtension;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadPriceListRelations::class
        ]);
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => $this->getApiEntityName()],
            'create.yml'
        );

        $relation = $this->getEntityManager()
            ->getRepository(PriceListToCustomerGroup::class)
            ->findOneBy([
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'website' => $this->getWebsiteForCreateAction(),
                'customerGroup' => $this->getReference('customer_group.group3'),
            ]);

        static::assertSame(11, $relation->getSortOrder());
        static::assertTrue($relation->isMergeAllowed());

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
        $relationId2 = $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_GROUP_5)->getId();

        $this->cdelete(
            ['entity' => $this->getApiEntityName()],
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
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        'customerGroup' => $this->getReference('customer_group.group1')->getId()
                    ],
                    [
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
                        'customerGroup' => $this->getReference('customer_group.group3')->getId()
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
            ->getRepository(PriceListToCustomerGroup::class)
            ->find($relationId);

        static::assertSame(21, $updatedRelation->getSortOrder());
        static::assertTrue($updatedRelation->isMergeAllowed());
        static::assertEquals(
            $this->getReference(LoadPriceLists::PRICE_LIST_2),
            $updatedRelation->getPriceList()
        );

        $this->assertFirstRelationMessageSent();
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResourceForFirstRelation('priceList', $relation->getPriceList()->getId());
        $this->assertGetSubResourceForFirstRelation('customerGroup', $relation->getCustomerGroup()->getId());
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetRelationshipForFirstRelation(
            'priceList',
            PriceList::class,
            $relation->getPriceList()->getId()
        );

        $this->assertGetRelationshipForFirstRelation(
            'customerGroup',
            CustomerGroup::class,
            $relation->getCustomerGroup()->getId()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getApiEntityName(): string
    {
        return 'pricelisttocustomergroups';
    }

    /**
     * {@inheritDoc}
     */
    protected function getAliceFilesFolderName(): string
    {
        return 'price_list_to_customer_group';
    }

    /**
     * {@inheritDoc}
     */
    protected function getFirstRelation()
    {
        return $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_GROUP_1);
    }

    protected function getWebsiteForCreateAction(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
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
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        'customerGroup' => $this->getReference('customer_group.group1')->getId()
                    ],
                ]
            ]
        );
    }
}
