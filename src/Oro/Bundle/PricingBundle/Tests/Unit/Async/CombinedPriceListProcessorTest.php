<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListProcessor;
use Oro\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerFactory;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
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
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRelationTriggerFactory
     */
    protected $triggerFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $cplBuilderClass = 'Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder';
        $this->cplBuilder = $this->getMockBuilder($cplBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplWebsiteBuilderClass = 'Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder';
        $this->cplWebsiteBuilder = $this->getMockBuilder($cplWebsiteBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplAccountGroupBuilderClass = 'Oro\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder';
        $this->cplAccountGroupBuilder = $this->getMockBuilder($cplAccountGroupBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $cplAccountBuilderClass = 'Oro\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder';
        $this->cplAccountBuilder = $this->getMockBuilder($cplAccountBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->logger = $this->getMock(LoggerInterface::class);
        $this->triggerFactory = $this->getMockBuilder(PriceListRelationTriggerFactory::class)
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
     * @param PriceListRelationTrigger $trigger
     */
    public function testProcess(PriceListRelationTrigger $trigger)
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithException()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $this->triggerFactory->method('createFromArray')->willThrowException(new \Exception());

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $account = $this->getMock(Account::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $accountGroup */
        $accountGroup = $this->getMock('Oro\Bundle\AccountBundle\Entity\AccountGroup');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);
        $trigger = new PriceListRelationTrigger();
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
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $account = $this->getMock(Account::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);

        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website)
            ->setAccount($account);
        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

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
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $account */
        $accountGroup = $this->getMock(AccountGroup::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);

        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website)
            ->setAccountGroup($accountGroup);
        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

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
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Website $website */
        $website = $this->getMock(Website::class);

        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website);
        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

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
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMock(SessionInterface::class);

        $trigger = new PriceListRelationTrigger();
        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

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
