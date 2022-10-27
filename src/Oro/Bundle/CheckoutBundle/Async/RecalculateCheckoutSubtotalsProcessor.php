<?php

namespace Oro\Bundle\CheckoutBundle\Async;

use Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Processor for recalculate checkout subtotals
 */
class RecalculateCheckoutSubtotalsProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected CheckoutSubtotalUpdater $checkoutSubtotalUpdater;

    public function __construct(CheckoutSubtotalUpdater $checkoutSubtotalUpdater)
    {
        $this->checkoutSubtotalUpdater = $checkoutSubtotalUpdater;
        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $this->checkoutSubtotalUpdater->recalculateInvalidSubtotals();
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $e,
                    'topic' => RecalculateCheckoutSubtotalsTopic::getName(),
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [RecalculateCheckoutSubtotalsTopic::getName()];
    }
}
