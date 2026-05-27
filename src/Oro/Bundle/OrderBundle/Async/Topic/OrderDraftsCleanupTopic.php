<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for deleting outdated draft orders and order line items.
 */
class OrderDraftsCleanupTopic extends AbstractTopic
{
    public const string NAME = 'oro.order.draft_session.cleanup.order_draft';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Deletes outdated draft orders and order line items';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('draftLifetimeDays')
            ->setDefault('draftLifetimeDays', 7)
            ->setAllowedTypes('draftLifetimeDays', 'int');
    }
}
