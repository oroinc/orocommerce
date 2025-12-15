<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Processes a chunk of products to populate fallback values.
 */
final class ProductFallbackProcessChunkTopic extends AbstractTopic
{
    public const string NAME = 'oro.platform.post_upgrade.process_chunk';
    public const string JOB_ID = 'job_id';
    public const string PRODUCT_IDS = 'product_ids';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Processes a chunk of products to populate fallback values.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired([self::JOB_ID, self::PRODUCT_IDS]);
        $resolver->setAllowedTypes(self::JOB_ID, ['int']);
        $resolver->setAllowedTypes(self::PRODUCT_IDS, ['array']);

        $resolver->setNormalizer(self::PRODUCT_IDS, static function (Options $options, array $value) {
            if (empty($value)) {
                throw new InvalidOptionsException('The product_ids option must contain at least one identifier.');
            }

            return array_map('intval', $value);
        });
    }
}
