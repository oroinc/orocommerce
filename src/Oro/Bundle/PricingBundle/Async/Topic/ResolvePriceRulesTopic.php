<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves price lists rules and updates actuality of price lists.
 */
class ResolvePriceRulesTopic implements TopicInterface
{
    public const NAME = 'oro_pricing.price_rule.build';

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
