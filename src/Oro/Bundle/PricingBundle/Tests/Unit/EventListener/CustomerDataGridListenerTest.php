<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\EventListener\CustomerDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    protected function setUp(): void
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->createMock('Doctrine\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap(
            [
                ['OroPricingBundle:PriceListToCustomer', $this->repository],
            ]
        );
        parent::setUp();
        $this->listener = new CustomerDataGridListener($this->registry);
    }

    /**
     * {@internal}
     */
    protected function createRelation()
    {
        $relation = new PriceListToCustomer();
        /** @var Customer $customer */
        $customer = new Customer();
        /** @var PriceList|\PHPUnit\Framework\MockObject\MockObject $priceList */
        $priceList = $this->createMock('Oro\Bundle\PricingBundle\Entity\PriceList');
        /** @var Website|\PHPUnit\Framework\MockObject\MockObject $website */
        $website = $this->createMock('Oro\Bundle\WebsiteBundle\Entity\Website');
        $website->method('getId')->willReturn(1);
        $relation->setCustomer($customer);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
