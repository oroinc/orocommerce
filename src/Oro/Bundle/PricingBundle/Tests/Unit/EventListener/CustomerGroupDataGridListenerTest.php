<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerGroupDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    protected function setUp(): void
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->createMock('Doctrine\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap(
            [
                ['OroPricingBundle:PriceListToCustomerGroup', $this->repository],
            ]
        );
        parent::setUp();
        $this->listener = new CustomerGroupDataGridListener($this->registry);
    }

    /**
     * @return BasePriceListRelation
     */
    protected function createRelation()
    {
        $relation = new PriceListToCustomerGroup();
        /** @var CustomerGroup|\PHPUnit\Framework\MockObject\MockObject $customerGroup */
        $customerGroup = $this->createMock('Oro\Bundle\CustomerBundle\Entity\CustomerGroup');
        /** @var PriceList|\PHPUnit\Framework\MockObject\MockObject $priceList */
        $priceList = $this->createMock('Oro\Bundle\PricingBundle\Entity\PriceList');
        /** @var Website|\PHPUnit\Framework\MockObject\MockObject $website */
        $website = $this->createMock('Oro\Bundle\WebsiteBundle\Entity\Website');
        $website->method('getId')->willReturn(1);
        $relation->setCustomerGroup($customerGroup);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
