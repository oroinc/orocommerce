<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Async;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use OroB2B\Bundle\PricingBundle\Async\CombinedPriceListProcessor;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CombinedPriceListProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListsBuilder
     */
    protected $cplBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WebsiteCombinedPriceListsBuilder
     */
    protected $cplWebsiteBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountGroupCombinedPriceListsBuilder
     */
    protected $cplAccountGroupBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountCombinedPriceListsBuilder
     */
    protected $cplAccountBuilder;
    /**
     * @var CombinedPriceListProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListChangeTriggerFactory
     */
    protected $triggerFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $cplBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder';
        $this->cplBuilder = $this->getMockBuilder($cplBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplWebsiteBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder';
        $this->cplWebsiteBuilder = $this->getMockBuilder($cplWebsiteBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplAccountGroupBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder';
        $this->cplAccountGroupBuilder = $this->getMockBuilder($cplAccountGroupBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplAccountBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder';
        $this->cplAccountBuilder = $this->getMockBuilder($cplAccountBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->logger = $this->getMock(LoggerInterface::class);
        $this->triggerFactory = $this->getMockBuilder(PriceListChangeTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CombinedPriceListProcessor(
            $this->cplBuilder,
            $this->cplWebsiteBuilder,
            $this->cplAccountGroupBuilder,
            $this->cplAccountBuilder,
            $this->dispatcher,
            $this->logger,
            $this->triggerFactory
        );
    }

    /**
     * @dataProvider processDataProvider
     * @param PriceListChangeTrigger $trigger
     */
    public function testProcess(PriceListChangeTrigger $trigger)
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->triggerFactory->method('createFromMessage')->willReturn($trigger);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $account = $this->getMock(Account::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $accountGroup */
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);
        $trigger = new PriceListChangeTrigger();
        return [
            [
                'trigger' => $trigger,
            ],
            [
                'trigger' => $trigger->setWebsite($website),
            ],
            [
                'trigger' => $trigger->setAccountGroup($accountGroup),
            ],
            [
                'trigger' => $trigger->setAccount($account),
            ],
        ];
    }

    /**
     * @dataProvider dispatchAccountScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchAccountScopeEvent(array $builtList)
    {
        $this->cplAccountBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn($builtList);
        if (isset($builtList['account'])) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(AccountCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $account = $this->getMock(Account::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);

        $trigger = new PriceListChangeTrigger();
        $trigger->setWebsite($website)
            ->setAccount($account);
        $this->triggerFactory->method('createFromMessage')->willReturn($trigger);

        $this->processor->process($message, $session);
    }

    /**
     * @return array
     */
    public function dispatchAccountScopeEventDataProvider()
    {
        return [
            'with account scope' => [
                'builtList' => [
                    'account' => [
                        1 => [
                            1 => true,
                            2 => true
                        ]
                    ]
                ]
            ],
            'without account scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchAccountGroupScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchAccountGroupScopeEvent(array $builtList)
    {
        $this->cplAccountGroupBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn($builtList);
        if ($builtList) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(AccountGroupCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $account */
        $accountGroup = $this->getMock(AccountGroup::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);

        $trigger = new PriceListChangeTrigger();
        $trigger->setWebsite($website)
            ->setAccountGroup($accountGroup);
        $this->triggerFactory->method('createFromMessage')->willReturn($trigger);

        $this->processor->process($message, $session);
    }

    /**
     * @return array
     */
    public function dispatchAccountGroupScopeEventDataProvider()
    {
        return [
            'with account group scope' => [
                'builtList' => [
                    1 => [
                        1 => true,
                        2 => true
                    ]
                ]
            ],
            'without account group scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchWebsiteScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchWebsiteScopeEvent(array $builtList)
    {
        $this->cplWebsiteBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn($builtList);

        if ($builtList) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(WebsiteCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);

        $trigger = new PriceListChangeTrigger();
        $trigger->setWebsite($website);
        $this->triggerFactory->method('createFromMessage')->willReturn($trigger);

        $this->processor->process($message, $session);
    }

    /**
     * @return array
     */
    public function dispatchWebsiteScopeEventDataProvider()
    {
        return [
            'with account group scope' => [
                'builtList' => [1, 2, 3]
            ],
            'without account group scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchConfigScopeEventDataProvider
     * @param bool $isBuilt
     */
    public function testDispatchConfigScopeEvent($isBuilt)
    {
        $this->cplBuilder->expects($this->once())
            ->method('isBuilt')
            ->willReturn($isBuilt);

        if ($isBuilt) {
            $this->dispatcher->expects($this->once())
                ->method('dispatch')
                ->with(ConfigCPLUpdateEvent::NAME);
        } else {
            $this->dispatcher->expects($this->never())
                ->method('dispatch');
        }
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $trigger = new PriceListChangeTrigger();
        $this->triggerFactory->method('createFromMessage')->willReturn($trigger);

        $this->processor->process($message, $session);
    }

    /**
     * @return array
     */
    public function dispatchConfigScopeEventDataProvider()
    {
        return [
            'built' => [
                'builtList' => true
            ],
            'not built' => [
                'builtList' => false
            ],
        ];
    }
}
