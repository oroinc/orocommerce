<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\PricingBundle\EventListener\AccountDataGridListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountDataGridListenerTest extends AbstractPriceListRelationDataGridListenerTest
{
    public function setUp()
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository';
        $this->repository = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroPricingBundle:PriceListToAccount', $this->repository]
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
        $priceList = $this->getMock('Oro\Bundle\PricingBundle\Entity\PriceList');
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->getMock('Oro\Bundle\WebsiteBundle\Entity\Website');
        $website->method('getId')->willReturn(1);
        $relation->setAccount($account);
        $relation->setWebsite($website);
        $relation->setPriceList($priceList);

        return $relation;
    }
}
