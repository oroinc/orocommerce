<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Schedule prices combination for a given list of Combined Price Lists.
 */
class ActualizeCombinedPriceListTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro_pricing.price_lists.cpl.rebuild.list';

    private CombinedPriceListProvider $combinedPriceListProvider;

    public function __construct(
        CombinedPriceListProvider $combinedPriceListProvider
    ) {
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Schedule prices combination for a given list of Combined Price Lists';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('cpl')
            ->info('Array of IDs of existing Combined Price Lists for which combined prices should be rebuilt.')
            ->default([])
            ->allowedTypes('int[]', 'string[]')
            ->normalize(function (Options $options, $value): array {
                $cpls = [];
                foreach ($value as $cplId) {
                    $cpls[] = $this->combinedPriceListProvider->getCombinedPriceListById($cplId);
                }

                return $cpls;
            });
    }

    public function createJobName($messageBody): string
    {
        $ids = array_map(
            function ($cpl) {
                if ($cpl instanceof CombinedPriceList) {
                    return $cpl->getId();
                }

                return $cpl;
            },
            $messageBody['cpl']
        );
        sort($ids);

        return self::getName() . ':' . md5(json_encode($ids));
    }
}
