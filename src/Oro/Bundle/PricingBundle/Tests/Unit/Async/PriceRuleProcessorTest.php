<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\PriceListCalculationNotificationAlert;
use Oro\Bundle\PricingBundle\Async\PriceRuleProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PriceRuleProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProductPriceBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $priceBuilder;

    /** @var NotificationAlertManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationAlertManager;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var PriceRuleProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->priceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);
        $this->triggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new PriceRuleProcessor(
            $this->doctrine,
            $this->priceBuilder,
            $this->notificationAlertManager,
            $this->triggerHandler,
            $this->producer
        );
        $this->processor->setFeatureChecker($this->featureChecker);
        $this->processor->setLogger($this->logger);
    }

    /**
     * @param mixed $body
     *
     * @return MessageInterface
     */
    private function getMessage($body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [ResolvePriceRulesTopic::getName()],
            PriceRuleProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenSinglePriceListNotFound()
    {
        $priceListId = 1;
        $body = ['product' => [$priceListId => [2]]];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::never()))
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(PriceList::class, $priceListId)
            ->willReturn(null);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('PriceList entity with identifier 1 not found.');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWhenOneOfPriceListsNotFound()
    {
        $priceListId1 = 1;
        $priceListId2 = 2;
        $productIds = [2];
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => $priceListId2, 'updatedAt' => new \DateTime()]);
        $body = ['product' => [$priceListId1 => $productIds, $priceListId2 => $productIds]];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::never()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, $priceListId1],
                [PriceList::class, $priceListId2],
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $priceList2
            );

        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceList')
            ->with($priceList2, $productIds);

        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList2);

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList2], true);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('PriceList entity with identifier 1 not found.');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessExceptionInBuildByPriceList()
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId, 'updatedAt' => new \DateTime()]);

        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->with(
                PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                $priceListId
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Rule build.',
                ['exception' => $exception]
            );

        $this->notificationAlertManager->expects(self::once())
            ->method('addNotificationAlert')
            ->with(self::isInstanceOf(PriceListCalculationNotificationAlert::class));

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessSeveralWithSingleExceptionInBuildByPriceList()
    {
        $priceListId1 = 1;
        $priceListId2 = 2;
        $productIds = [2];
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getEntity(PriceList::class, ['id' => $priceListId1, 'updatedAt' => new \DateTime()]);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => $priceListId2, 'updatedAt' => new \DateTime()]);
        $body = ['product' => [$priceListId1 => $productIds, $priceListId2 => $productIds]];
        $exception = new \Exception('Some error');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, $priceListId1],
                [PriceList::class, $priceListId2],
            )
            ->willReturnOnConsecutiveCalls($priceList1, $priceList2);

        $this->notificationAlertManager->expects(self::exactly(2))
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->withConsecutive(
                [
                    PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                    $priceListId1
                ],
                [
                    PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                    $priceListId2
                ]
            );
        $this->priceBuilder->expects(self::exactly(2))
            ->method('buildByPriceList')
            ->withConsecutive(
                [$priceList1, $productIds],
                [$priceList2, $productIds]
            )
            ->willReturnCallback(
                static function (PriceList  $priceList, array $productIds) use ($priceListId1, $exception) {
                    if ($priceList->getId() === $priceListId1) {
                        throw $exception;
                    }
                }
            );

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Unexpected exception occurred during Price Rule build.', ['exception' => $exception]);

        $this->notificationAlertManager->expects(self::once())
            ->method('addNotificationAlert')
            ->with(self::isInstanceOf(PriceListCalculationNotificationAlert::class));

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList2], true);
        $this->triggerHandler->expects(self::never())
            ->method('handlePriceListTopic');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessRetryableExceptionInBuildByPriceList()
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId, 'updatedAt' => new \DateTime()]);

        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->with(
                PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                $priceListId
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Rule build.',
                ['exception' => $exception]
            );

        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessSeveralWithSingleRetryableExceptionInBuildByPriceList()
    {
        $priceListId1 = 1;
        $priceListId2 = 2;
        $productIds = [2];
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getEntity(PriceList::class, ['id' => $priceListId1, 'updatedAt' => new \DateTime()]);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => $priceListId2, 'updatedAt' => new \DateTime()]);
        $body = ['product' => [$priceListId1 => $productIds, $priceListId2 => $productIds]];
        $exception = $this->createMock(DeadlockException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('rollback');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, $priceListId1],
                [PriceList::class, $priceListId2],
            )
            ->willReturnOnConsecutiveCalls($priceList1, $priceList2);

        $this->notificationAlertManager->expects(self::exactly(2))
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->withConsecutive(
                [
                    PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                    $priceListId1
                ],
                [
                    PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                    $priceListId2
                ]
            );
        $this->priceBuilder->expects(self::exactly(2))
            ->method('buildByPriceList')
            ->withConsecutive(
                [$priceList1, $productIds],
                [$priceList2, $productIds]
            )
            ->willReturnCallback(
                static function (PriceList  $priceList, array $productIds) use ($priceListId1, $exception) {
                    if ($priceList->getId() === $priceListId1) {
                        throw $exception;
                    }
                }
            );

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Rule build.',
                ['exception' => $exception]
            );
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->triggerHandler->expects(self::once())
            ->method('handlePriceListTopic')
            ->with(
                ResolvePriceRulesTopic::getName(),
                $priceList1,
                $productIds
            );

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList2], true);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcess()
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId, 'updatedAt' => new \DateTime()]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->with(
                PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                $priceListId
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds);

        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList);

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList], true);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }

    public function testProcessWithFlatPricingEnabled(): void
    {
        $priceListId = 1;
        $productIds = [2];
        $body = ['product' => [$priceListId => $productIds]];

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId, 'updatedAt' => new \DateTime()]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects((self::once()))
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->with(
                PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                $priceListId
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds);

        $em->expects(self::once())
            ->method('refresh')
            ->with($priceList);

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([$priceList], true);

        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveFlatPriceTopic::getName(), ['priceList' => $priceList->getId(), 'products' => $productIds]);

        $this->processor->addFeature('oro_price_lists_flat');
        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
