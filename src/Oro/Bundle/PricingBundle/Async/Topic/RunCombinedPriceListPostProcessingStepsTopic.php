<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Run logic after combined price lists are processed.
 * Remove unused combined price lists, trigger products indexation.
 */
class RunCombinedPriceListPostProcessingStepsTopic implements TopicInterface
{
    public const NAME = 'oro_pricing.price_lists.run_cpl_post_processing_steps';

    public static function getName(): string
    {
        return static::NAME;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'relatedJobId'
        ]);

        $resolver->setAllowedTypes('relatedJobId', ['int']);
    }
}
