<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Doctrine\ORM\EntityRepository;

/**
 * @dbIsolationPerTest
 */
class PriceListToCustomerTest extends AbstractApiPriceListRelationTest
{
    /**
     * @return string
     */
    protected function getApiEntityName(): string
    {
        return 'pricelisttocustomers';
    }

    /**
     * @return string
     */
    protected function getAliceFilesFolderName(): string
    {
        return 'price_list_to_customer';
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository(): EntityRepository
    {
        return $this->getEntityManager()->getRepository(PriceListToCustomer::class);
    }

    /**
     * @return array
     */
    protected function getDeleteListFilter()
    {
        return [
            'website' => $this->getReference('US')->getId(),
        ];
    }

    /**
     * @return BasePriceListRelation
     */
    protected function getFirstRelation(): BasePriceListRelation
    {
        return $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_1);
    }

    /**
     * @return array
     */
    protected function getExpectedRebuildMessagesOnDeleteList(): array
    {
        $messages = [
            $this->prepareRebuildPriceListMessagesForEntity(
                $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_1)
            ),
            $this->prepareRebuildPriceListMessagesForEntity(
                $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_2)
            ),
            $this->prepareRebuildPriceListMessagesForEntity(
                $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_3)
            ),
            $this->prepareRebuildPriceListMessagesForEntity(
                $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_4)
            ),
            $this->prepareRebuildPriceListMessagesForEntity(
                $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_5)
            ),
            $this->prepareRebuildPriceListMessagesForEntity(
                $this->getReference(LoadPriceListRelations::PRICE_LIST_TO_CUSTOMER_US_6)
            )
        ];

        return $messages;
    }

    /**
     * @param BasePriceListRelation $entity
     *
     * @return array
     */
    protected function prepareRebuildPriceListMessagesForEntity(BasePriceListRelation $entity): array
    {
        /** @var PriceListToCustomer $entity */

        $website = $entity->getWebsite();
        $customer = $entity->getCustomer();
        $customerGroup = $customer->getGroup();

        return [
            PriceListRelationTrigger::WEBSITE => $website->getId(),
            PriceListRelationTrigger::ACCOUNT => $customer->getId(),
            PriceListRelationTrigger::ACCOUNT_GROUP => $customerGroup ? $customerGroup->getId() : null,
            PriceListRelationTrigger::FORCE => false,
        ];
    }
}
