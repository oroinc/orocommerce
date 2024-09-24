<?php

namespace Oro\Bundle\CheckoutBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for recalculate checkout subtotals
 */
class RecalculateCheckoutSubtotalsTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.checkout.recalculate_checkout_subtotals';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Recalculates checkout subtotals';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
