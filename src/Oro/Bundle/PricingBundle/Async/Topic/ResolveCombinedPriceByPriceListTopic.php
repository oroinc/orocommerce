<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 */
class ResolveCombinedPriceByPriceListTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro_pricing.price_lists.cpl.resolve_prices';

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Combine prices for active and ready to rebuild Combined Price List for a given list of price lists ' .
            'and products.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'array');
    }

    public function createJobName($messageBody): string
    {
        return self::getName() . ':' . md5(json_encode($messageBody));
    }
}
