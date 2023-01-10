<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Run logic after combined price lists are processed.
 * Remove unused combined price lists, trigger products indexation.
 */
class RunCombinedPriceListPostProcessingStepsTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.price_lists.run_cpl_post_processing_steps';

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Run logic after combined price lists are processed. ' .
            'Remove unused combined price lists, trigger products indexation';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define('relatedJobId')
            ->required()
            ->allowedTypes('int');

        $resolver
            ->define('cpls')
            ->default([])
            ->allowedTypes('int[]');
    }
}
