<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves flat prices by price version.
 */
class ResolveVersionedFlatPriceTopic extends AbstractTopic implements JobAwareTopicInterface
{
    private const NAME = 'oro_pricing.flat_price.resolve_by_version';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Resolves flat product prices by version.';
    }

    #[\Override]
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

    #[\Override]
    public function createJobName($messageBody): string
    {
        return ResolveFlatPriceTopic::getName() . ':v' . $messageBody['version'];
    }
}
