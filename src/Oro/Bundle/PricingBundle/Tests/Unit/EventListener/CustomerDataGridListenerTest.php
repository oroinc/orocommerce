<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PriceListToCustomerRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                ['OroPricingBundle:PriceListToCustomer', $this->repository],
            ]);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->listener = new CustomerDataGridListener($doctrine);
    }

    /**
     * {@inheritDoc}
     */
    protected function createRelation(): PriceListToCustomer
    {
        $relation = new PriceListToCustomer();
        $customer = new Customer();
        $priceList = $this->createMock(PriceList::class);
        $website = $this->createMock(Website::class);
        $website->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $relation->setCustomer($customer);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
