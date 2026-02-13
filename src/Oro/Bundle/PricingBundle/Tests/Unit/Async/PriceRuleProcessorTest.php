<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\PriceListCalculationNotificationAlert;
use Oro\Bundle\PricingBundle\Async\PriceRuleProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PriceRuleProcessorTest extends TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var ProductPriceBuilder|MockObject */
    private $priceBuilder;

    /** @var NotificationAlertManager|MockObject */
    private $notificationAlertManager;

    /** @var PriceListTriggerHandler|MockObject */
    private $triggerHandler;

    /** @var MessageProducerInterface|MockObject */
    private $producer;

    /** @var FeatureChecker|MockObject */
    private $featureChecker;

    /** @var DependentPriceListProvider|MockObject */
    private $dependentPriceListProvider;

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
        $this->dependentPriceListProvider = $this->createMock(DependentPriceListProvider::class);

        $this->processor = new PriceRuleProcessor(
            $this->doctrine,
            $this->priceBuilder,
            $this->notificationAlertManager,
            $this->triggerHandler,
            $this->producer
        );
        $this->processor->setFeatureChecker($this->featureChecker);
        $this->processor->setLogger($this->logger);
        $this->processor->setDependentPriceListProvider($this->dependentPriceListProvider);
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

        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertByOperationAndItemIdForCurrentUser')
            ->with(
                PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                $priceListId2
            );

        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [self::greaterThanOrEqual(0)],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
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

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                self::callback(function ($data) use ($priceListId2) {
                    return isset($data['sourcePriceListId'])
                        && $data['sourcePriceListId'] === $priceListId2
                        && isset($data['version'])
                        && is_int($data['version'])
                        && $data['version'] >= 0;
                })
            );

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
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [self::greaterThanOrEqual(0)],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
        $this->priceBuilder->expects(self::exactly(4))
            ->method('setVersion')
            ->withConsecutive(
                [self::greaterThanOrEqual(0)],
                [null],
                [self::greaterThanOrEqual(0)],
                [null]
            );
        $this->priceBuilder->expects(self::exactly(2))
            ->method('buildByPriceListWithoutTriggers')
            ->withConsecutive(
                [$priceList1, $productIds],
                [$priceList2, $productIds]
            )
            ->willReturnCallback(
                static function (PriceList $priceList, array $productIds) use ($priceListId1, $exception) {
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

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                self::callback(function ($data) use ($priceListId2) {
                    return isset($data['sourcePriceListId'])
                        && $data['sourcePriceListId'] === $priceListId2
                        && isset($data['version'])
                        && is_int($data['version'])
                        && $data['version'] >= 0;
                })
            );

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
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [self::greaterThanOrEqual(0)],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
        $this->priceBuilder->expects(self::exactly(4))
            ->method('setVersion')
            ->withConsecutive(
                [self::greaterThanOrEqual(0)],
                [null],
                [self::greaterThanOrEqual(0)],
                [null]
            );
        $this->priceBuilder->expects(self::exactly(2))
            ->method('buildByPriceListWithoutTriggers')
            ->withConsecutive(
                [$priceList1, $productIds],
                [$priceList2, $productIds]
            )
            ->willReturnCallback(
                static function (PriceList $priceList, array $productIds) use ($priceListId1, $exception) {
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

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                self::callback(function ($data) use ($priceListId2) {
                    return isset($data['sourcePriceListId'])
                        && $data['sourcePriceListId'] === $priceListId2
                        && isset($data['version'])
                        && is_int($data['version'])
                        && $data['version'] >= 0;
                })
            );

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
        $this->priceBuilder->expects(self::exactly(2))
            ->method('setVersion')
            ->withConsecutive(
                [self::greaterThanOrEqual(0)],
                [null]
            );
        $this->priceBuilder->expects(self::once())
            ->method('buildByPriceListWithoutTriggers')
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

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                self::callback(function ($data) use ($priceListId) {
                    return isset($data['sourcePriceListId'])
                        && $data['sourcePriceListId'] === $priceListId
                        && isset($data['version'])
                        && is_int($data['version'])
                        && $data['version'] >= 0;
                })
            );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
