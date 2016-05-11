<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Doctrine\ORM\UnitOfWork;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use OroB2B\Bundle\PricingBundle\RecalculateTriggersFiller\ScopeRecalculateTriggersFiller;
use OroB2B\Bundle\PricingBundle\Tests\Functional\Model\Stub\CombinedPriceListQueueListenerStub;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class PriceListChangeTriggerHandlerTest extends WebTestCase
{
    /**
     * @var Website
     */
    protected $website;

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var CombinedPriceListQueueListenerStub
     */
    protected $listener;

    /**
     * @var ScopeRecalculateTriggersFiller|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggersFiller;

    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations'
            ]
        );

        $this->account = $this->getReference('account.level_1.2');
        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);

        $this->listener = new CombinedPriceListQueueListenerStub();
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(PriceListQueueChangeEvent::BEFORE_CHANGE, [$this->listener, 'onQueueChanged']);

        $this->triggersFiller = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\RecalculateTriggersFiller\ScopeRecalculateTriggersFiller')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new PriceListChangeTriggerHandler(
            $this->getContainer()->get('doctrine'),
            $dispatcher,
            $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor'),
            $this->triggersFiller
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $registry = $this->getContainer()->get('doctrine');
        $registry->getManager()->clear();
        parent::tearDown();
    }

    public function testHandleWebsiteChange()
    {
        $this->handler->handleWebsiteChange($this->website);
        $expected = (new PriceListChangeTrigger())->setWebsite($this->website);
        $this->assertTriggerWasPersisted($expected);
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    public function testHandleAccountChange()
    {
        $this->handler->handleAccountChange($this->account, $this->website);

        $expected = (new PriceListChangeTrigger())
            ->setWebsite($this->website)
            ->setAccount($this->account)
            ->setAccountGroup($this->account->getGroup());
        $this->assertTriggerWasPersisted($expected);
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    public function testHandleConfigChange()
    {
        $this->handler->handleConfigChange(false);
        $expected = (new PriceListChangeTrigger());
        $this->assertTriggerWasPersisted($expected);
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    public function testHandleAccountGroupChange()
    {
        $this->handler->handleAccountGroupChange($this->account->getGroup(), $this->website);

        $expected = (new PriceListChangeTrigger())
            ->setWebsite($this->website)
            ->setAccountGroup($this->account->getGroup());
        $this->assertTriggerWasPersisted($expected);
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    public function testHandlePriceListStatusChange()
    {
        $priceList = new PriceList();
        $this->triggersFiller->expects($this->once())
            ->method('fillTriggersByPriceList')
            ->with($priceList);

        $this->handler->handlePriceListStatusChange($priceList);
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    public function testHandleFullRebuild()
    {

        $this->handler->handleFullRebuild(false);
        $expected = (new PriceListChangeTrigger())->setForce(true);
        $this->assertTriggerWasPersisted($expected);
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    public function testHandleAccountGroupRemove()
    {
        $this->handler->handleAccountGroupRemove($this->account->getGroup());
        /** @var PriceListChangeTrigger[] $triggers */
        $triggers = $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->findAll();
        $this->assertCount(1, $triggers);
        $this->assertNotNull(current($triggers)->getAccount());
        $this->assertRealTimeCPLQueueListenerDispatched();
    }

    /**
     * @param PriceListChangeTrigger $expected
     */
    protected function assertTriggerWasPersisted(PriceListChangeTrigger $expected)
    {
        /** @var UnitOfWork $uow */
        $uow = $this->getContainer()->get('doctrine')->getManager()->getUnitOfWork();
        $scheduledForInsert = $uow->getScheduledEntityInsertions();

        $this->assertCount(1, $scheduledForInsert);
        $this->assertEquals($expected, current($scheduledForInsert));
    }

    protected function assertRealTimeCPLQueueListenerDispatched()
    {
        $this->assertTrue($this->listener->hasCollectionChanges(), "CPL Queue Listener was not dispatched");
    }
}
