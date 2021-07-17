<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\NotificationMessages;
use Oro\Bundle\PricingBundle\Async\PriceListAssignedProductsProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceListAssignedProductsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var PriceListProductAssignmentBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $assignmentBuilder;

    /** @var Messenger|\PHPUnit\Framework\MockObject\MockObject */
    private $messenger;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var PriceListAssignedProductsProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);
        $this->messenger = $this->createMock(Messenger::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->triggerHandler = $this->createMock(PriceListTriggerHandler::class);

        $this->processor = new PriceListAssignedProductsProcessor(
            $this->doctrine,
            $this->logger,
            $this->assignmentBuilder,
            $this->messenger,
            $this->translator
        );
        $this->processor->setTriggerHandler($this->triggerHandler);
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
            [Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS],
            PriceListAssignedProductsProcessor::getSubscribedTopics()
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

    public function testProcessWhenSinglePriceListNotFound()
    {
        $priceListId = 1;
        $body = ['product' => [$priceListId => [2]]];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');

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

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList2, $productIds);

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

        $exception = new \Exception('Some error');

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);

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
            ->with(PriceList::class, $priceListId)
            ->willReturn($priceList);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price List Assigned Products build.',
                ['exception' => $exception]
            );

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.pricing.notification.price_list.error.product_assignment_build')
            ->willReturn('Error occurred during price list product assignments build');

        $this->messenger->expects($this->once())
            ->method('send')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                Message::STATUS_ERROR,
                'Error occurred during price list product assignments build',
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
                    NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                    PriceList::class,
                    $priceListId1
                ],
                [
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                    PriceList::class,
                    $priceListId2
                ]
            );
        $this->assignmentBuilder->expects($this->exactly(2))
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
                'Unexpected exception occurred during Price List Assigned Products build.',
                ['exception' => $exception]
            );

        $messageText = 'oro.pricing.notification.price_list.error.product_assignment_build TRANS';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.pricing.notification.price_list.error.product_assignment_build')
            ->willReturn($messageText);
        $this->messenger->expects($this->once())
            ->method('send')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                Message::STATUS_ERROR,
                $messageText,
                PriceList::class,
                $priceListId1
            );

        $this->triggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
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
                    NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                    PriceList::class,
                    $priceListId1
                ],
                [
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                    PriceList::class,
                    $priceListId2
                ]
            );
        $this->assignmentBuilder->expects($this->exactly(2))
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
                'Unexpected exception occurred during Price List Assigned Products build.',
                ['exception' => $exception]
            );
        $this->messenger->expects($this->never())
            ->method('send');

        $this->triggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(
                Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
                $priceList1,
                $productIds
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
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('commit');

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, $priceListId)
            ->willReturn($priceList);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                PriceList::class,
                $priceListId
            );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessage($body), $this->getSession())
        );
    }
}
