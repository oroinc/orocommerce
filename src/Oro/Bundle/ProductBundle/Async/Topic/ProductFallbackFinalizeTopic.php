<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Finalizes product fallback updates by resolving notification alerts when processing is complete.
 */
final class ProductFallbackFinalizeTopic extends AbstractTopic
{
    public const string NAME = 'oro.platform.post_upgrade.finalize';
    public const string JOB_ID = 'job_id';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Finalizes product fallback updates by resolving notification alerts when processing is complete.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setDefined(self::JOB_ID);
    }
}
