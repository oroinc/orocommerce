<?php

namespace Oro\Bundle\CheckoutBundle\Async;

use Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Remove duplicated oro.checkout.recalculate_checkout_subtotals messages as all messages are same.
 */
class RecalculateCheckoutSubtotalsMessageFilter implements MessageFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopic(RecalculateCheckoutSubtotalsTopic::getName())) {
            return;
        }

        $isFirst = true;
        foreach ($buffer->getMessagesForTopic(RecalculateCheckoutSubtotalsTopic::getName()) as $messageId => $message) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $buffer->removeMessage($messageId);
        }
    }
}
