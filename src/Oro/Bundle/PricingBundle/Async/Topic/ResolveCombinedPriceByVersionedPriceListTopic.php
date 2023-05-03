<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine prices for active and ready to rebuild Combined Price List based on the price version.
 */
class ResolveCombinedPriceByVersionedPriceListTopic extends AbstractTopic implements JobAwareTopicInterface
{
    private const NAME = 'oro_pricing.price_lists.cpl.resolve_prices_by_version';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Combines prices for active and ready to rebuild Combined Price List for a given version of price list.';
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

    public function createJobName($messageBody): string
    {
        return self::getName() . ':v' . $messageBody['version'];
    }
}
