<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves flat prices by price version.
 */
class ResolveVersionedFlatPriceTopic implements TopicInterface
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
            ->setRequired('version')
            ->setAllowedTypes('version', 'int');

        $resolver
            ->setRequired('priceLists')
            ->setAllowedTypes('priceLists', 'int[]');
    }
}
