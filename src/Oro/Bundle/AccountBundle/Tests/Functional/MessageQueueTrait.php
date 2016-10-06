<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method static ContainerInterface getContainer
 */
trait MessageQueueTrait
{
    use MessageQueueAssertTrait;

    protected function cleanScheduledMessages()
    {
        $this->sendScheduledMessages();
        $this->getMessageCollector()->clear();
    }

    /**
     * @return ProductMessageHandler
     */
    abstract public function getMessageHandler();

    protected function sendScheduledMessages()
    {
        if ($this->getMessageHandler()) {
            $this->getMessageHandler()->sendScheduledMessages();
        }
    }
}
