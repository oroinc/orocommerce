<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\NotificationMessages;
use Oro\Bundle\PricingBundle\Async\PriceListAssignedProductsProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class PriceListAssignedProductsProcessorTest extends AbstractPriceProcessorTest
{
    use EntityTrait;

    /**
     * @var PriceListProductAssignmentBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $assignmentBuilder;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var PriceListAssignedProductsProcessor
     */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->assignmentBuilder = $this->createMock(PriceListProductAssignmentBuilder::class);

        $this->processor = new PriceListAssignedProductsProcessor(
            $this->triggerFactory,
            $this->assignmentBuilder,
            $this->logger,
            $this->messenger,
            $this->translator,
            $this->registry
        );
    }

    public function testProcessInvalidArgumentException()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $message = $this->prepareMessageForProcessInvalidArgumentException();

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionWithoutTrigger()
    {
        $exception = new \Exception('Some error');

        $message = $this->prepareMessageForProcessExceptionWithoutTrigger($exception);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price List Assigned Products build',
                ['exception' => $exception]
            );

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionWithTrigger()
    {
        $exception = new \Exception('Some error');
        $data = ['test' => 1];
        $message = $this->prepareMessageWithBody($data);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $this->priceListRepository->expects($this->once())
            ->method('find')
            ->with($priceList->getId())
            ->willReturn($priceList);

        $productIds = [2];
        $trigger = new PriceListTrigger([$priceList->getId() => $productIds]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn($trigger);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $productIds)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Price List Assigned Products build',
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
                $priceList->getId()
            );

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        $data = ['test' => 1];
        $body = json_encode($data);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $repository = $this->assertEntityManagerCalled();
        $repository->expects($this->once())
            ->method('find')
            ->with($priceList->getId())
            ->willReturn($priceList);

        $productId = 2;
        $trigger = new PriceListTrigger([$priceList->getId() => [$productId]]);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn($trigger);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, [$productId]);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                PriceList::class,
                $priceList->getId()
            );

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithoutPriceList()
    {
        $priceListId = 1001;
        $productId = 2002;

        $data = [PriceListTriggerFactory::PRODUCT => [$priceListId => [$productId]]];

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => $priceListId]);

        $repository = $this->assertEntityManagerCalled();
        $repository->expects($this->once())
            ->method('find')
            ->with($priceList->getId())
            ->willReturn($priceList);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($data));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn(new PriceListTrigger($data[PriceListTriggerFactory::PRODUCT]));

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, [$productId]);

        $this->messenger->expects($this->once())
            ->method('remove')
            ->with(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
                PriceList::class,
                $priceList->getId()
            );

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    /**
     * @return PriceListRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertEntityManagerCalled()
    {
        $repository = $this->createMock(PriceListRepository::class);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repository);

        $manager->expects($this->once())
            ->method('beginTransaction');

        $manager->expects(($this->once()))
            ->method('commit');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        return $repository;
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS], $this->processor->getSubscribedTopics());
    }
}
