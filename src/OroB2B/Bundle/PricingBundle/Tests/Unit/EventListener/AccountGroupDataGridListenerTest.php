<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\EventListener\AccountGroupDataGridListener;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    public function setUp()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:PriceListToAccountGroup', $this->repository]
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
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceList');
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->method('getId')->willReturn(1);
        $relation->setAccountGroup($accountGroup);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
