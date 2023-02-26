<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class CustomerGroupDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PriceListToCustomerGroupRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(PriceListToCustomerGroup::class)
            ->willReturn($this->repository);

        $this->listener = new CustomerGroupDataGridListener($doctrine);
    }

    /**
     * {@inheritDoc}
     */
    protected function createRelation(int $objectId): BasePriceListRelation
    {
        $customerGroup = new CustomerGroup();
        ReflectionUtil::setId($customerGroup, $objectId);

        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $relation = new PriceListToCustomerGroup();
        $relation->setCustomerGroup($customerGroup);
        $relation->setWebsite($website);
        $relation->setPriceList($this->createMock(PriceList::class));

        return $relation;
    }
}
