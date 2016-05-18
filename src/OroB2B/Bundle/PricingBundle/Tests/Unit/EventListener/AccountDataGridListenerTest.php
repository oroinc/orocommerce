<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\EventListener\AccountDataGridListener;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    public function setUp()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:PriceListToAccount', $this->repository]
        ]);
        parent::setUp();
        $this->listener = new AccountDataGridListener($this->registry);
    }

    /**
     * {@internal}
     */
    protected function createRelation()
    {
        $relation = new PriceListToAccount();
        /** @var Account $account */
        $account = new Account();
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $priceList */
        $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\PriceList');
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->getMock('OroB2B\Bundle\WebsiteBundle\Entity\Website');
        $website->method('getId')->willReturn(1);
        $relation->setAccount($account);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
