<?php

namespace Oro\Bundle\OrderBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to recalculate order total for all orders and save it in serialized data
 * if it does not match stored order total.
 */
class PrecalculateOrderTotalTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.order.precalculate_order_total';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Recalculates order total for all orders and save it in serialized data';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('firstOrderId')
            ->setAllowedTypes('firstOrderId', 'int');
        $resolver
            ->setDefined('lastOrderId')
            ->setAllowedTypes('lastOrderId', 'int');
    }
}
