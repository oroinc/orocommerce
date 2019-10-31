<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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

    /**
     * {@inheritDoc}
     */
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

    protected function getWebsiteForTest(): Website
    {
        return $this->getEntityManager()->getRepository(Website::class)->getDefaultWebsite();
    }

    public function testCreate()
    {
        $this->cleanScheduledRelationMessages();

        $this->post(
            ['entity' => $this->getApiEntityName()],
            'create.yml'
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

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relation = $this->getFirstRelation();
        $id = $relation->getId();
        $this->patch(
            ['entity' => $this->getApiEntityName(), 'id' => (string)$id],
            'update.yml'
        );

        $updatedRelation = $this->getEntityRepository()->find($id);

        static::assertSame(999, $updatedRelation->getSortOrder());
        static::assertFalse($updatedRelation->isMergeAllowed());

        $this->assertFirstRelationMessageSent();
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResourceForFirstRelation('priceList', $relation->getPriceList()->getId());
        $this->assertGetSubResourceForFirstRelation('customer', $relation->getCustomer()->getId());
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
            'customer',
            Customer::class,
            $relation->getCustomer()->getId()
        );
    }
}
