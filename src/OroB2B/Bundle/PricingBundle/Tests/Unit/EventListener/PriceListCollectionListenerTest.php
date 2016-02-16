<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
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
            ->with('OroB2BPricingBundle:PriceListChangeTrigger')
            ->willReturn($this->manager);
        $this->listener = new PriceListCollectionListener($registry);
        parent::setUp();
    }

    /**
     * @dataProvider onChangeCollectionBeforeDataProvider
     * @param PriceListQueueChangeEvent $event
     * @param PriceListChangeTrigger $changedPriceListCollection
     */
    public function testOnChangeCollectionBefore(
        PriceListQueueChangeEvent $event,
        PriceListChangeTrigger $changedPriceListCollection
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
        $changedPriceListCollectionWithConfig = new PriceListChangeTrigger();
        $changedPriceListCollectionWithWebsite = clone $changedPriceListCollectionWithConfig;
        $changedPriceListCollectionWithWebsite->setWebsite($website);
        $changedPriceListCollectionWithAccount = clone $changedPriceListCollectionWithWebsite;
        $changedPriceListCollectionWithAccount->setAccount($account);
        $changedPriceListCollectionWithAccountGroup = clone $changedPriceListCollectionWithWebsite;
        $changedPriceListCollectionWithAccountGroup->setAccountGroup($accountGroup);

        return [
            'website' => [
                'event' => new PriceListQueueChangeEvent($website),
                'collectionEntity' => $changedPriceListCollectionWithWebsite
            ],
            'config' => [
                'event' => new PriceListQueueChangeEvent(),
                'collectionEntity' => $changedPriceListCollectionWithConfig
            ],
            'account' => [
                'event' => new PriceListQueueChangeEvent($account, $website),
                'collectionEntity' => $changedPriceListCollectionWithAccount
            ],
            'accountGroup' => [
                'event' => new PriceListQueueChangeEvent($accountGroup, $website),
                'collectionEntity' => $changedPriceListCollectionWithAccountGroup
            ]
        ];
    }
}
