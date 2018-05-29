<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\PricingBundle\Async\PriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PriceListProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListTriggerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerFactory;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerHandler;

    /**
     * @var CombinedPriceListsBuilderFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $combinedPriceListsBuilderFacade;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CombinedPriceListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var DatabaseExceptionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseExceptionHelper;

    /**
     * @var PriceListProcessor
     */
    protected $priceRuleProcessor;

    protected function setUp()
    {
        $this->triggerFactory = $this->getMockBuilder(PriceListTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceResolver = $this->getMockBuilder(MergePricesCombiningStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->repository = $this->getMockBuilder(CombinedPriceListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->databaseExceptionHelper = $this->getMockBuilder(DatabaseExceptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggerHandler = $this->getMockBuilder(CombinedPriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceRuleProcessor = new PriceListProcessor(
            $this->triggerFactory,
            $this->registry,
            $this->combinedPriceListsBuilderFacade,
            $this->logger,
            $this->databaseExceptionHelper,
            $this->triggerHandler
        );
    }

    public function testProcessInvalidArgumentException()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Message is invalid: %s',
                    'Test message'
                )
            );

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willThrowException(new InvalidArgumentException('Test message'));

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->priceRuleProcessor->process($message, $session));
    }

    public function testProcessDeadlock()
    {
        /** @var PDOException $exception */
        $exception = $this->getMockBuilder(PDOException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willThrowException($exception);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Combined Price Lists build', ['exception' => $exception]);

        $this->triggerFactory->expects($this->never())
            ->method('createFromArray');

        $driverException = $this->createMock(DriverException::class);
        $this->databaseExceptionHelper->expects($this->once())
            ->method('getDriverException')
            ->with($exception)
            ->willReturn($driverException);
        $this->databaseExceptionHelper->expects($this->once())
            ->method('isDeadlock')
            ->willReturn(true);

        $this->assertEquals(MessageProcessorInterface::REQUEUE, $this->priceRuleProcessor->process($message, $session));
    }

    public function testProcessException()
    {
        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('rollback');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->will($this->throwException($exception));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Combined Price Lists build', ['exception' => $exception]);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->databaseExceptionHelper->expects($this->never())
            ->method('isDeadlock');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->priceRuleProcessor->process($message, $session)
        );
    }

    public function testProcess()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $productIds = [2];
        $trigger = new PriceListTrigger([$priceList->getId() => $productIds]);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn($trigger);

        $cpl = new CombinedPriceList();

        $repository = $this->assertEntityManagerCalled();
        $repository->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceList->getId()])
            ->willReturn([$cpl]);
        $repository->method('getPriceListIdsByCpls')
            ->with([$cpl])
            ->willReturn([$priceList->getId()]);

        $this->repository->method('getCombinedPriceListsByPriceList')
            ->with($priceList, true)
            ->willReturn([$cpl]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuild')
            ->with([$cpl], $productIds);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->priceRuleProcessor->process($message, $session));
    }

    public function testProcessWithoutPriceList()
    {
        $priceListId = 1001;
        $productId = 2002;

        $data = [PriceListTriggerFactory::PRODUCT => [$priceListId => [$productId]]];

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn(new PriceListTrigger($data[PriceListTriggerFactory::PRODUCT]));

        /** @var CombinedPriceList $priceList */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 3003]);

        $repository = $this->assertEntityManagerCalled();
        $repository->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceListId])
            ->willReturn([$cpl]);
        $repository->method('getPriceListIdsByCpls')
            ->with([$cpl])
            ->willReturn([$priceListId]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuild')
            ->with([$cpl], [$productId]);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->priceRuleProcessor->process($message, $session));
    }

    /**
     * @return CombinedPriceListToPriceListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function assertEntityManagerCalled()
    {
        $repository = $this->createMock(CombinedPriceListToPriceListRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [CombinedPriceList::class, $this->repository],
                    [CombinedPriceListToPriceList::class, $repository],
                ]
            );

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('commit');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $repository;
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::RESOLVE_COMBINED_PRICES], $this->priceRuleProcessor->getSubscribedTopics());
    }
}
