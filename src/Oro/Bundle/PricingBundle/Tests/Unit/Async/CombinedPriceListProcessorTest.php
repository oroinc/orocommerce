<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListProcessor;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerFactory;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CombinedPriceListProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListsBuilderFacade
     */
    protected $combinedPriceListsBuilderFacade;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PriceListRelationTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $triggerHandler;

    /**
     * @var CombinedPriceListProcessor
     */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->triggerFactory = $this->getMockBuilder(PriceListRelationTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);

        /** @var CombinedPriceListProcessor processor */
        $this->processor = new CombinedPriceListProcessor(
            $this->logger,
            $this->triggerFactory,
            $this->registry,
            $this->triggerHandler,
            $this->combinedPriceListsBuilderFacade
        );
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param PriceListRelationTrigger $trigger
     * @param string $expectedMethod
     */
    public function testProcess(PriceListRelationTrigger $trigger, string $expectedMethod)
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->never()))
            ->method('rollback');

        $em->expects(($this->once()))
            ->method('commit');

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method($expectedMethod);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    /**
     * @param \Exception $exception
     * @param int $isDeadlockCheck
     * @param bool $isDeadlock
     * @param string $result
     *
     * @dataProvider getProcessWithExceptionDataProvider
     */
    public function testProcessWithException($exception, $isDeadlockCheck, $isDeadlock, $result)
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $em->expects(($this->never()))
            ->method('commit');

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('rollback');
        $this->triggerHandler->expects($this->never())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->method('createFromArray')->willThrowException($exception);

        $this->assertEquals($result, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function getProcessWithExceptionDataProvider()
    {
        return [
            'process InvalidArgumentException' => [
                'exception' => new InvalidArgumentException(),
                'isDeadlockCheck' => 0,
                'isDeadlock' => false,
                'result' => MessageProcessorInterface::REJECT
            ],
            'process deadlock' => [
                'exception' => $this->createMock(DeadlockException::class),
                'isDeadlockCheck' => 1,
                'isDeadlock' => true,
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'process exception' => [
                'exception' => new \Exception(),
                'isDeadlockCheck' => 1,
                'isDeadlock' => false,
                'result' => MessageProcessorInterface::REJECT
            ]
        ];
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Customer $customer */
        $customer = $this->createMock(Customer::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerGroup $customerGroup */
        $customerGroup = $this->createMock('Oro\Bundle\CustomerBundle\Entity\CustomerGroup');

        /** @var \PHPUnit\Framework\MockObject\MockObject|Website $website */
        $website = $this->createMock(Website::class);

        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website);
        return [
            [
                'trigger' => new PriceListRelationTrigger(),
                'expectedMethod' => 'rebuildAll'
            ],
            [
                'trigger' => $trigger,
                'expectedMethod' => 'rebuildForWebsites'
            ],
            [
                'trigger' => (clone $trigger)->setCustomerGroup($customerGroup),
                'expectedMethod' => 'rebuildForCustomerGroups'
            ],
            [
                'trigger' => (clone $trigger)->setCustomer($customer),
                'expectedMethod' => 'rebuildForCustomers'
            ],
        ];
    }

    /**
     * @dataProvider dispatchCustomerScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchCustomerScopeEvent(array $builtList)
    {
        $this->prepareMocksForEvents();

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Customer $customer */
        $customer = $this->createMock(Customer::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Website $website */
        $website = $this->createMock(Website::class);

        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website)
            ->setCustomer($customer);
        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

        $this->processor->process($message, $session);
    }

    /**
     * @return array
     */
    public function dispatchCustomerScopeEventDataProvider()
    {
        return [
            'with customer scope' => [
                'builtList' => [
                    'customer' => [
                        1 => [
                            1 => true,
                            2 => true
                        ]
                    ]
                ]
            ],
            'without customer scope' => [
                'builtList' => []
            ],
        ];
    }

    /**
     * @dataProvider dispatchCustomerGroupScopeEventDataProvider
     * @param array $builtList
     */
    public function testDispatchCustomerGroupScopeEvent(array $builtList)
    {
        $this->prepareMocksForEvents();

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerGroup $customer */
        $customerGroup = $this->createMock(CustomerGroup::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Website $website */
        $website = $this->createMock(Website::class);

        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website)
            ->setCustomerGroup($customerGroup);
        $this->triggerFactory->method('createFromArray')->willReturn($trigger);

        $this->processor->process($message, $session);
    }

    /**
     * @return array
     */
    public function dispatchCustomerGroupScopeEventDataProvider()
    {
        return [
            'with customer group scope' => [
                'builtList' => [
                    1 => [
                        1 => true,
                        2 => true
                    ]
                ]
            ],
            'without customer group scope' => [
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
        $this->prepareMocksForEvents();

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Website $website */
        $website = $this->createMock(Website::class);

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
            'with customer group scope' => [
                'builtList' => [1, 2, 3]
            ],
            'without customer group scope' => [
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
        $this->prepareMocksForEvents();

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')->willReturn('');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

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

    protected function prepareMocksForEvents()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->never()))
            ->method('rollback');

        $em->expects(($this->once()))
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->combinedPriceListsBuilderFacade->expects($this->once())->method('dispatchEvents');
    }
}
