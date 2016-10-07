<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;

/**
 * @method static ContainerInterface getContainer
 */
trait MessageQueueTrait
{
    use MessageQueueAssertTrait;

    protected function cleanScheduledMessages()
    {
        $this->sendScheduledMessages();
        $this->getMessageCollector()->enable();
        $this->getMessageCollector()->clear();
    }

    /**
     * @return array
     */
    protected function getQueueMessageTraces()
    {
        $this->sendScheduledMessages();

        return array_values(array_filter(
            $this->getMessageProducer()->getSentMessages(),
            function (array $trace) {
                return $this->topic === $trace['topic'];
            }
        ));
    }

    protected function sendScheduledMessages()
    {
        self::getContainer()->get('oro_pricing.price_list_trigger_handler')
            ->sendScheduledTriggers();
    }

    /**
     * @return MessageCollector
     */
    protected function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }

    /**
     * @param array $trace
     * @return int
     */
    protected function getPriceListIdFromTrace(array $trace)
    {
        return $trace['message'][PriceListTriggerFactory::PRICE_LIST];
    }

    /**
     * @param array $trace
     * @return int|null
     */
    protected function getProductIdFromTrace(array $trace)
    {
        return $trace['message'][PriceListTriggerFactory::PRODUCT];
    }
}
