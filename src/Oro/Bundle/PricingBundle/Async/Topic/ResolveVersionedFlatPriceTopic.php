<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves flat prices by price version.
 */
class ResolveVersionedFlatPriceTopic extends AbstractTopic
{
    private const NAME = 'oro_pricing.flat_price.resolve_by_version';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Resolves flat product prices by version.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define('version')
            ->info('Prices version in price list.')
            ->required()
            ->allowedTypes('int');

        $resolver
            ->define('priceLists')
            ->info('List of price lists.')
            ->required()
            ->allowedTypes('int[]');
    }
}
