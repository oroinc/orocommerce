<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class CustomerDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PriceListToCustomerRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($this->repository);

        $this->listener = new CustomerDataGridListener($doctrine);
    }

    /**
     * {@inheritDoc}
     */
    protected function createRelation(int $objectId): PriceListToCustomer
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, $objectId);

        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $relation = new PriceListToCustomer();
        $relation->setCustomer($customer);
        $relation->setWebsite($website);
        $relation->setPriceList($this->createMock(PriceList::class));

        return $relation;
    }
}
