<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Updates supported currencies for combined price lists by price lists.
 */
class ResolveCombinedPriceListCurrenciesTopic implements TopicInterface
{
    public const NAME = 'oro_pricing.price_lists.cpl.resolve_currencies';

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
