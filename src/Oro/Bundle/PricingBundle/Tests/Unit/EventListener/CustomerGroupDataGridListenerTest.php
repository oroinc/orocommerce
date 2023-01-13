<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerGroupDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PriceListToCustomerGroupRepository::class);

        $em = $this->createMock(ObjectManager::class);
        $em->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                ['OroPricingBundle:PriceListToCustomerGroup', $this->repository],
            ]);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->listener = new CustomerGroupDataGridListener($doctrine);
    }

    /**
     * {@inheritDoc}
     */
    protected function createRelation(): BasePriceListRelation
    {
        $relation = new PriceListToCustomerGroup();
        $customerGroup = $this->createMock(CustomerGroup::class);
        $priceList = $this->createMock(PriceList::class);
        $website = $this->createMock(Website::class);
        $website->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $relation->setCustomerGroup($customerGroup);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
