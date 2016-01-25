<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\PricingBundle\EventListener\PriceListCollectionListener;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PriceListCollectionListener */
    protected $listener;

    /** @var  EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    public function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BPricingBundle:ChangedPriceListCollection')
            ->willReturn($this->manager);
        $this->listener = new PriceListCollectionListener($registry);
        parent::setUp();
    }

    /**
     * @dataProvider onChangeCollectionBeforeDataProvider
     * @param PriceListCollectionChange $event
     * @param ChangedPriceListCollection $changedPriceListCollection
     */
    public function testOnChangeCollectionBefore(
        PriceListCollectionChange $event,
        ChangedPriceListCollection $changedPriceListCollection
    ) {
        $this->manager->expects($this->once())->method('persist')->with($changedPriceListCollection);
        $this->listener->onChangeCollectionBefore($event);
    }

    /**
     * @return array
     */
    public function onChangeCollectionBeforeDataProvider()
    {
        $account = new Account();
        $accountGroup = new AccountGroup();
        $website = new Website();
        $changedPriceListCollectionWithConfig = new ChangedPriceListCollection();
        $changedPriceListCollectionWithWebsite = clone $changedPriceListCollectionWithConfig;
        $changedPriceListCollectionWithWebsite->setWebsite($website);
        $changedPriceListCollectionWithAccount = clone $changedPriceListCollectionWithWebsite;
        $changedPriceListCollectionWithAccount->setAccount($account);
        $changedPriceListCollectionWithAccountGroup = clone $changedPriceListCollectionWithWebsite;
        $changedPriceListCollectionWithAccountGroup->setAccountGroup($accountGroup);

        return [
            'website' => [
                'event' => new PriceListCollectionChange($website),
                'collectionEntity' => $changedPriceListCollectionWithWebsite
            ],
            'config' => [
                'event' => new PriceListCollectionChange(),
                'collectionEntity' => $changedPriceListCollectionWithConfig
            ],
            'account' => [
                'event' => new PriceListCollectionChange($account, $website),
                'collectionEntity' => $changedPriceListCollectionWithAccount
            ],
            'accountGroup' => [
                'event' => new PriceListCollectionChange($accountGroup, $website),
                'collectionEntity' => $changedPriceListCollectionWithAccountGroup
            ]
        ];
    }
}
