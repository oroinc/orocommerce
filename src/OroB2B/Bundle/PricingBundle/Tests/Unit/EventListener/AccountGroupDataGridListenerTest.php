<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use Oro\Bundle\PricingBundle\EventListener\AccountGroupDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    public function setUp()
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroPricingBundle:PriceListToAccountGroup', $this->repository]
        ]);
        parent::setUp();
        $this->listener = new AccountGroupDataGridListener($this->registry);
    }

    /**
     * @return BasePriceListRelation
     */
    protected function createRelation()
    {
        $relation = new PriceListToAccountGroup();
        /** @var AccountGroup|\PHPUnit_Framework_MockObject_MockObject $accountGroup */
        $accountGroup = $this->getMock('Oro\Bundle\AccountBundle\Entity\AccountGroup');
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock('Oro\Bundle\PricingBundle\Entity\PriceList');
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->getMock('Oro\Bundle\WebsiteBundle\Entity\Website');
        $website->method('getId')->willReturn(1);
        $relation->setAccountGroup($accountGroup);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
