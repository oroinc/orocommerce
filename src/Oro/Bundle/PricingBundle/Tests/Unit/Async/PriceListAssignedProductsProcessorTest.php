<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\NotificationMessages;
use Oro\Bundle\PricingBundle\Async\PriceListAssignedProductsProcessor;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class PriceListAssignedProductsProcessorTest extends AbstractPriceProcessorTest
{
    use EntityTrait;

    /**
     * @var PriceListProductAssignmentBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $assignmentBuilder;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
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

        $this->assignmentBuilder = $this->getMockBuilder(PriceListProductAssignmentBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $message = $this->prepareMessageForProcessInvalidArgumentException();

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionWithoutTrigger()
    {
        $exception = new \Exception('Some error');

        $message = $this->prepareMessageForProcessExceptionWithoutTrigger($exception);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
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
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceListTrigger($priceList, $product);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->triggerFactory->expects($this->once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn($trigger);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $product);

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $product)
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

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects(($this->once()))
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceListTrigger($priceList, $product);

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

        $this->assignmentBuilder->expects($this->once())
            ->method('buildByPriceList')
            ->with($priceList, $product);

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

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS], $this->processor->getSubscribedTopics());
    }
}
