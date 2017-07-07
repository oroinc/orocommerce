<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class PriceListToCustomerTest extends AbstractApiPriceListRelationTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                LoadPriceListRelations::class,
            ]
        );
    }

    /**
     * @return string
     */
    protected function getAliceFilesFolderName(): string
    {
        return 'price_list_to_customer';
    }

    /**
     * @inheritDoc
     */
    protected function getApiEntityName(): string
    {
        return 'pricelisttocustomers';
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository(): EntityRepository
    {
        return $this->getEntityManager()->getRepository(PriceListToCustomer::class);
    }

    /**
     * @return PriceListToCustomer
     */
    protected function getFirstRelation(): PriceListToCustomer
    {
        return $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_1);
    }

    public function getWebsiteForTest(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }

    public function testCreate()
    {
        $this->cleanScheduledRelationMessages();

        $this->post(
            ['entity' => $this->getApiEntityName()],
            $this->getAliceFilesFolderName().'/create.yml'
        );

        $priceList = $this->getReference('price_list_4');
        $website = $this->getWebsiteForTest();
        $customer = $this->getReference('customer.level_1_1');

        $createdCustomer = $this->getEntityRepository()->findOneBy(
            [
                'sortOrder' => 12,
                'mergeAllowed' => false,
                'priceList' => $priceList->getId(),
                'website' => $website->getId(),
                'customer' => $customer->getId(),
            ]
        );
        $this->assertNotNull($createdCustomer);

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $website->getId(),
                PriceListRelationTrigger::ACCOUNT => $customer->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledRelationMessages();

        $customerRelationUS1 = $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_1);
        $customerRelationUS6 = $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_6);
        $customerRelationCanada1 = $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_CANADA_1);

        $this->cdelete(
            ['entity' => $this->getApiEntityName()],
            [
                'filter' => [
                    'id' => [
                        $customerRelationUS1->getId(),
                        $customerRelationUS6->getId(),
                        $customerRelationCanada1->getId(),
                    ],
                ],
            ]
        );

        $entitiesAfterDelete = $this->getEntityRepository()->findBy(
            [
                'id' =>
                    [
                        $customerRelationUS6->getId(),
                        $customerRelationUS1->getId(),
                        $customerRelationCanada1->getId(),
                    ],
            ]
        );

        static::assertCount(0, $entitiesAfterDelete);
        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $customerRelationUS1->getWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => $customerRelationUS1->getCustomer()->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $customerRelationUS6->getWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => $customerRelationUS6->getCustomer()->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => $customerRelationUS6->getCustomer()->getGroup()->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $customerRelationCanada1->getWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => $customerRelationCanada1->getCustomer()->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testDelete()
    {
        $this->cleanScheduledRelationMessages();

        $relation = $this->getFirstRelation();
        $id = $relation->getId();

        $this->delete(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $id,
            ]
        );

        $this->assertNull(
            $this->getEntityRepository()->find($id)
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $relation->getWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => $relation->getCustomer()->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relation = $this->getFirstRelation();
        $id = $relation->getId();
        $this->patch(
            ['entity' => $this->getApiEntityName(), 'id' => (string)$id],
            $this->getAliceFilesFolderName().'/update.yml'
        );

        $updatedRelation = $this->getEntityRepository()->find($id);

        static::assertSame(999, $updatedRelation->getSortOrder());
        static::assertFalse($updatedRelation->isMergeAllowed());
        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $relation->getWebsite()->getId(),
                PriceListRelationTrigger::ACCOUNT => $relation->getCustomer()->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResource($relation->getId(), 'priceList', $relation->getPriceList()->getId());
        $this->assertGetSubResource($relation->getId(), 'customer', $relation->getCustomer()->getId());
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $response = $this->getRelationship(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $relation->getId(),
                'association' => 'priceList',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(PriceList::class),
                    'id' => (string)$relation->getPriceList()->getId(),
                ],
            ],
            $response
        );

        $response = $this->getRelationship(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $relation->getId(),
                'association' => 'customer',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(Customer::class),
                    'id' => (string)$relation->getCustomer()->getId(),
                ],
            ],
            $response
        );
    }
}
