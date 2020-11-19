<?php

namespace Oro\Bundle\CheckoutBundle\Async;

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
        if (!$buffer->hasMessagesForTopic(Topics::RECALCULATE_CHECKOUT_SUBTOTALS)) {
            return;
        }

        $isFirst = true;
        foreach ($buffer->getMessagesForTopic(Topics::RECALCULATE_CHECKOUT_SUBTOTALS) as $messageId => $message) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $buffer->removeMessage($messageId);
        }
    }
}
