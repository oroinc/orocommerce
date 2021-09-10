<?php

namespace Oro\Bundle\CheckoutBundle\Async;

use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class RecalculateCheckoutSubtotalsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
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
     * {@inheritDoc}
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
                    'topic' => Topics::RECALCULATE_CHECKOUT_SUBTOTALS,
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RECALCULATE_CHECKOUT_SUBTOTALS];
    }
}
