<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\PriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListActivationStatusHelperInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PriceListProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CombinedPriceListsBuilderFacade|\PHPUnit\Framework\MockObject\MockObject */
    private $combinedPriceListsBuilderFacade;

    /** @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var CombinedPriceListActivationStatusHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $activationStatusHelper;

    /** @var PriceListProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->combinedPriceListsBuilderFacade = $this->createMock(CombinedPriceListsBuilderFacade::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->activationStatusHelper = $this->createMock(CombinedPriceListActivationStatusHelperInterface::class);

        $this->processor = new PriceListProcessor(
            $this->doctrine,
            $this->logger,
            $this->combinedPriceListsBuilderFacade,
            $this->triggerHandler,
            $this->activationStatusHelper
        );
    }

    /**
     * @param mixed $body
     *
     * @return MessageInterface
     */
    private function getMessage($body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::RESOLVE_COMBINED_PRICES],
            PriceListProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithInvalidMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage('invalid'), $this->getSession())
        );
    }

    public function testProcessWithEmptyMessage()
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage([]), $this->getSession())
        );
    }

    public function testProcessDeadlock()
    {
        $body = ['product' => [1 => [2]]];

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToPriceList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Lists build.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessException()
    {
        $body = ['product' => [1 => [2]]];

        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToPriceList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Lists build.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcess()
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        $cpl1 = $this->getEntity(CombinedPriceList::class, ['id' => 10]);
        $cpl2 = $this->getEntity(CombinedPriceList::class, ['id' => 20]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('commit');
        $em->expects(($this->never()))
            ->method('rollback');

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToPriceList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceListId])
            ->willReturn([$cpl1, $cpl2]);
        $this->activationStatusHelper->expects($this->exactly(2))
            ->method('isReadyForBuild')
            ->withConsecutive([$cpl1], [$cpl2])
            ->willReturnOnConsecutiveCalls(true, false);

        $repository->expects($this->once())
            ->method('getPriceListIdsByCpls')
            ->with([$cpl1])
            ->willReturn([$priceListId]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuild')
            ->with([$cpl1], $productIds);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithoutPriceList()
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 10]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('commit');
        $em->expects(($this->never()))
            ->method('rollback');

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToPriceList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->with([$priceListId])
            ->willReturn([$cpl]);
        $this->activationStatusHelper->expects($this->once())
            ->method('isReadyForBuild')
            ->with($cpl)
            ->willReturn(true);
        $repository->expects($this->once())
            ->method('getPriceListIdsByCpls')
            ->with([$cpl])
            ->willReturn([$priceListId]);

        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('rebuild')
            ->with([$cpl], $productIds);
        $this->combinedPriceListsBuilderFacade->expects($this->once())
            ->method('dispatchEvents');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessExceptionWithNotActiveTransaction()
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Error connection');

        $body = ['product' => [1 => [2]]];

        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback')
            ->willThrowException(new ConnectionException('Error connection'));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CombinedPriceList::class)
            ->willReturn($em);

        $repository = $this->createMock(CombinedPriceListToPriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToPriceList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getCombinedPriceListsByActualPriceLists')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Lists build.',
                ['exception' => $exception]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
