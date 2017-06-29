<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
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
        static::getMessageCollector()->clear();
    }

    protected function sendScheduledMessages()
    {
        self::getContainer()->get('oro_pricing.price_list_trigger_handler')
            ->sendScheduledTriggers();
    }

    protected function cleanScheduledRelationMessages()
    {
        $this->sendScheduledRelationMessages();

        static::getMessageCollector()->clear();
    }

    protected function sendScheduledRelationMessages()
    {
        static::getContainer()
            ->get('oro_pricing.price_list_relation_trigger_handler')
            ->sendScheduledTriggers();
    }
}
