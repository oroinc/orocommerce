<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method static ContainerInterface getContainer
 */
trait MessageQueueTrait
{
    /**
     * @var string
     */
    protected $topic;

    protected function cleanQueueMessageTraces()
    {
        $this->sendScheduledMessages();
        $this->getMessageProducer()->enable();
        $this->getMessageProducer()->clear();
    }

    /**
     * @param string|array $topic
     * @return array
     */
    protected function getQueueMessageTraces($topic = null)
    {
        $this->sendScheduledMessages();

        $messages = $this->getMessageProducer()->getSentMessages();
        if ($topic) {
            return array_values(array_filter(
                $messages,
                function (array $trace) use ($topic) {
                    return in_array($trace['topic'], (array)$topic, true);
                }
            ));
        } else {
            return $messages;
        }
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
