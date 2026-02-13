<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generates prices for single dependent price list.
 */
class GenerateSinglePriceListPricesByRulesTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.dependent_price_list_price.single_generate';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Job task to generate prices for single price list.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('priceListId')
            ->required()
            ->info('ID of the Price List')
            ->allowedTypes('int');

        $resolver->define('products')
            ->info('Collection of Product IDs for which prices should be recalculated.')
            ->default([])
            ->allowedTypes('int[]', 'string[]');

        $resolver->define('version')
            ->info('Unique version that may be used to get changed prices or affected products')
            ->required()
            ->allowedTypes('int', 'null', 'string');

        $resolver->define('jobId')
            ->info('Job ID of parent unique job.')
            ->required()
            ->allowedTypes('int', 'string');
    }
}
