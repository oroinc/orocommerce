<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 */
class ResolveCombinedPriceByPriceListTopic implements TopicInterface
{
    public const NAME = 'oro_pricing.price_lists.cpl.resolve_prices';

    public static function getName(): string
    {
        return static::NAME;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'array');
    }
}
