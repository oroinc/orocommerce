<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Async\NotificationMessages;
use Oro\Bundle\PricingBundle\Async\PriceRuleProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceRuleProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProductPriceBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $priceBuilder;

    /** @var Messenger|\PHPUnit\Framework\MockObject\MockObject */
    private $messenger;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var PriceRuleProcessor */
    private $processor;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->priceBuilder = $this->createMock(ProductPriceBuilder::class);
        $this->messenger = $this->createMock(Messenger::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->triggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new PriceRuleProcessor(
            $this->doctrine,
            $this->logger,
            $this->priceBuilder,
            $this->messenger,
            $this->translator
        );

        $this->processor->setTriggerHandler($this->triggerHandler);
        $this->processor->setTopic(new ResolvePriceRulesTopic());
        $this->processor->setFeatureChecker($this->featureChecker);
        $this->processor->setMessageProducer($this->producer);
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
            [ResolvePriceRulesTopic::getName()],
            PriceRuleProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenSinglePriceListNotFound()
    {
        $priceListId = 1;
        $body = ['product' => [$priceListId => [2]]];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceListId)
            ->willReturn(null);

        $this->logger->expects($this->once())
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
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, $priceListId1],
                [PriceList::class, $priceListId2],
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $priceList2
            );

        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList2, $productIds);

        $em->expects($this->once())
            ->method('refresh')
            ->with($priceList2);

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('updatePriceListsActuality')
            ->with([$priceList2], true);

        $this->logger->expects($this->once())
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
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                PriceList::class,
                $priceListId
            );
        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Rule build.',
                ['exception' => $exception]
            );

        $messageText = 'Error occurred during price rule build';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.pricing.notification.price_list.error.price_rule_build')
            ->willReturn('Error occurred during price rule build');
        $this->messenger->expects($this->once())
            ->method('send')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                Message::STATUS_ERROR,
                $messageText,
                PriceList::class,
                $priceListId
            );

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
        $em->expects($this->exactly(2))
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, $priceListId1],
                [PriceList::class, $priceListId2],
            )
            ->willReturnOnConsecutiveCalls($priceList1, $priceList2);

        $this->messenger->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                [
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                    PriceList::class,
                    $priceListId1
                ],
                [
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                    PriceList::class,
                    $priceListId2
                ]
            );
        $this->priceBuilder->expects($this->exactly(2))
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

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during Price Rule build.', ['exception' => $exception]);

        $messageText = 'Error occurred during price rule build';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.pricing.notification.price_list.error.price_rule_build')
            ->willReturn('Error occurred during price rule build');
        $this->messenger->expects($this->once())
            ->method('send')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                Message::STATUS_ERROR,
                $messageText,
                PriceList::class,
                $priceListId1
            );

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('updatePriceListsActuality')
            ->with([$priceList2], true);
        $this->triggerHandler->expects($this->never())
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
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                PriceList::class,
                $priceListId
            );
        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Rule build.',
                ['exception' => $exception]
            );

        $this->messenger->expects($this->never())
            ->method('send');

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
        $em->expects($this->exactly(2))
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [PriceList::class, $priceListId1],
                [PriceList::class, $priceListId2],
            )
            ->willReturnOnConsecutiveCalls($priceList1, $priceList2);

        $this->messenger->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                [
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                    PriceList::class,
                    $priceListId1
                ],
                [
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                    PriceList::class,
                    $priceListId2
                ]
            );
        $this->priceBuilder->expects($this->exactly(2))
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

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price Rule build.',
                ['exception' => $exception]
            );
        $this->messenger->expects($this->never())
            ->method('send');
        $this->triggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(
                ResolvePriceRulesTopic::getName(),
                $priceList1,
                $productIds
            );

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
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
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                PriceList::class,
                $priceListId
            );
        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds);

        $em->expects($this->once())
            ->method('refresh')
            ->with($priceList);

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
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
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceList->getId())
            ->willReturn($priceList);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                PriceList::class,
                $priceListId
            );
        $this->priceBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds);

        $em->expects($this->once())
            ->method('refresh')
            ->with($priceList);

        $repository = $this->createMock(PriceListRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
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
