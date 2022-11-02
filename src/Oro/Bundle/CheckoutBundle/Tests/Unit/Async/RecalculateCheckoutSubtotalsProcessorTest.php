<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Async;

use Oro\Bundle\CheckoutBundle\Async\RecalculateCheckoutSubtotalsProcessor;
use Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class RecalculateCheckoutSubtotalsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private CheckoutSubtotalUpdater|\PHPUnit\Framework\MockObject\MockObject $checkoutSubtotalUpdater;

    private MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message;

    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private RecalculateCheckoutSubtotalsProcessor $processor;

    protected function setUp(): void
    {
        $this->checkoutSubtotalUpdater = $this->createMock(CheckoutSubtotalUpdater::class);
        $this->message = $this->createMock(MessageInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->processor = new RecalculateCheckoutSubtotalsProcessor($this->checkoutSubtotalUpdater);
        $this->setUpLoggerMock($this->processor);
        $this->testSetLogger();
    }

    public function testProcessWhenThrowsException(): void
    {
        $exception = new \Exception('Test Exception');

        $this->checkoutSubtotalUpdater->expects(self::once())
            ->method('recalculateInvalidSubtotals')
            ->willThrowException($exception);

        $this->assertLoggerErrorMethodCalled();

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testProcess(): void
    {
        $this->checkoutSubtotalUpdater->expects(self::once())
            ->method('recalculateInvalidSubtotals');

        $this->assertLoggerNotCalled();

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [RecalculateCheckoutSubtotalsTopic::getName()],
            RecalculateCheckoutSubtotalsProcessor::getSubscribedTopics()
        );
    }
}
