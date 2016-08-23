<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Bundle\PricingBundle\Model\DTO\PriceRuleTriggerFactory;
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
        $this->getMessageProducer()->clearTraces();
    }

    /**
     * @return array
     */
    protected function getQueueMessageTraces()
    {
        $this->sendScheduledMessages();

        return array_filter(
            $this->getMessageProducer()->getTraces(),
            function (array $trace) {
                return $this->topic === $trace['topic'];
            }
        );
    }

    protected function sendScheduledMessages()
    {
        self::getContainer()->get('orob2b_pricing.price_rule_change_trigger_handler')
            ->sendScheduledTriggers();
    }

    /**
     * @return TraceableMessageProducer
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
        return $trace['message'][PriceRuleTriggerFactory::PRICE_LIST];
    }

    /**
     * @param array $trace
     * @return int|null
     */
    protected function getProductIdFromTrace(array $trace)
    {
        return $trace['message'][PriceRuleTriggerFactory::PRODUCT];
    }
}
