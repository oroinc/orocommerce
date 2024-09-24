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
 *
 * @internal Used to trigger CPL actualization by Price Debugging if needed
 */
class ActualizeCombinedPriceListTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro_pricing.price_lists.cpl.rebuild.list';

    public function __construct(
        private CombinedPriceListProvider $combinedPriceListProvider
    ) {
    }

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Schedule prices combination for a given list of Combined Price Lists';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('cpl')
            ->info('Array of IDs of existing Combined Price Lists for which combined prices should be rebuilt.')
            ->required()
            ->allowedTypes('int[]')
            ->normalize(function (Options $options, $value): array {
                $cpls = [];
                foreach ($value as $cplId) {
                    $cpl = $this->combinedPriceListProvider->getCombinedPriceListById($cplId);
                    if ($cpl) {
                        $cpls[] = $cpl;
                    }
                }

                return $cpls;
            });
    }

    #[\Override]
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
