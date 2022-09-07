<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine prices for active and ready to rebuild Combined Price List based on the price version.
 */
class ResolveCombinedPriceByVersionedPriceListTopic implements TopicInterface
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
            ->setRequired('version')
            ->setAllowedTypes('version', 'int');

        $resolver
            ->setRequired('priceLists')
            ->setAllowedTypes('priceLists', 'int[]');
    }
}
