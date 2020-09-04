<?php

namespace Oro\Bundle\VisibilityBundle\Async\Extension;

use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Calls post receive method of VisibilityMessageHandler so it can correctly handle both kernel.terminate and the end
 * of message processing event.
 */
class VisibilityMessageHandlerExtension extends AbstractExtension
{
    /** @var VisibilityMessageHandler */
    private $visibilityMessageHandler;

    /**
     * @param VisibilityMessageHandler $visibilityMessageHandler
     */
    public function __construct(VisibilityMessageHandler $visibilityMessageHandler)
    {
        $this->visibilityMessageHandler = $visibilityMessageHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context): void
    {
        $this->visibilityMessageHandler->sendScheduledMessages();
    }
}
