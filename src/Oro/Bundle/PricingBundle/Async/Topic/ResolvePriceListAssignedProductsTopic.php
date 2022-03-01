<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves combined price lists in case of price list product assigned rule is changed.
 */
class ResolvePriceListAssignedProductsTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.price_lists.resolve_assigned_products';

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Resolves combined price lists in case of price list product assigned rule is changed.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired('product');
        $resolver->setAllowedTypes('product', 'array');
    }
}
