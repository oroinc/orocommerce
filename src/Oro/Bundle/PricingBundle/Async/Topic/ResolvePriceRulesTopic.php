<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves price lists rules and updates actuality of price lists.
 */
class ResolvePriceRulesTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.price_rule.build';

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Resolves price lists rules and updates actuality of price lists.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'array');
    }
}
