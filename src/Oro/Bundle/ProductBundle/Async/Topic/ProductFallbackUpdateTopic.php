<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Schedules product fallback updates by splitting affected products into chunks.
 */
class ProductFallbackUpdateTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const string NAME = 'oro.platform.post_upgrade.update';
    public const string BATCH_SIZE_OPTION = 'batch_size';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Schedules product fallback updates by splitting affected products into chunks.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired(self::BATCH_SIZE_OPTION);
        $resolver->setAllowedTypes(self::BATCH_SIZE_OPTION, ['int']);
        $resolver->setNormalizer(self::BATCH_SIZE_OPTION, static function (Options $options, int $value) {
            if ($value <= 0) {
                throw new InvalidOptionsException('The batch size must be a positive integer.');
            }

            return $value;
        });
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        return sprintf(
            'oro:product:fallback:update:%d',
            $messageBody[self::BATCH_SIZE_OPTION] ?? 500
        );
    }
}
