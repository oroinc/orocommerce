<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Updates combined price lists in case of changes in structure of original price lists.
 */
class MassRebuildCombinedPriceListsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro_pricing.price_lists.cpl.mass_rebuild';

    public static function getName(): string
    {
        return static::NAME;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define('assignments')
            ->allowedTypes('array')
            ->default(function (OptionsResolver $resolver) {
                $resolver = $resolver->setPrototype(true);
                $this->configureAssignmentsResolver($resolver);
            });
    }

    private function configureAssignmentsResolver(OptionsResolver $assignmentItemResolver): void
    {
        $assignmentItemResolver
            ->define('force')
            ->info(
                'Set `force` to true to rebuild combined price lists for entities with default and self fallback. ' .
                'Passing `force` without restrictions by other entities means rebuilding all combined price lists.'
            )
            ->default(false)
            ->allowedTypes('bool');

        $assignmentItemResolver
            ->define('website')
            ->info('Website ID for which combined price lists should be rebuilt')
            ->default(null)
            ->allowedTypes('null', 'int')
            ->required();

        $assignmentItemResolver
            ->define('customer')
            ->info('Customer ID for whom combined price list should be rebuilt. Requires Website.')
            ->default(null)
            ->allowedTypes('null', 'int');

        $assignmentItemResolver
            ->define('customerGroup')
            ->info('Customer Group ID for which combined price lists should be rebuilt. Requires Website.')
            ->default(null)
            ->allowedTypes('null', 'int');
    }

    public static function getDescription(): string
    {
        return 'Updates combined price lists in case of changes in structure of original price lists.';
    }

    public function createJobName($messageBody): string
    {
        $data = [];
        foreach ($messageBody as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'getId')) {
                    $value = $value->getId();
                } else {
                    $value = serialize($value);
                }
            }
            $data[$key] = $value;
        }

        return self::getName() . ':' . md5(json_encode($data));
    }
}
