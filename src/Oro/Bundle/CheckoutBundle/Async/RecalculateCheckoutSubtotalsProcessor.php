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
use Psr\Log\LoggerInterface;

/**
 * Processor for recalculate checkout subtotals
 */
class RecalculateCheckoutSubtotalsProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var CheckoutSubtotalUpdater */
    protected $checkoutSubtotalUpdater;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        CheckoutSubtotalUpdater $checkoutSubtotalUpdater,
        LoggerInterface $logger
    ) {
        $this->checkoutSubtotalUpdater = $checkoutSubtotalUpdater;
        $this->logger = $logger;
    }

    /**
     * @param MessageInterface $message
     * @param SessionInterface $session
     * @return string
     */
    public function process(MessageInterface $message, SessionInterface $session)
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

    /**
     * @return array<string>
     */
    public static function getSubscribedTopics()
    {
        return [RecalculateCheckoutSubtotalsTopic::getName()];
    }
}
