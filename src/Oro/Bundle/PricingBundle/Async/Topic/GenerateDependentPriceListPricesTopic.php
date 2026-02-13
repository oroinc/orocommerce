<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generates prices for dependent price lists based on the Price Rules for product prices of the given version.
 */
class GenerateDependentPriceListPricesTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const BUFFER_SIZE = 5000;
    public const NAME = 'oro_pricing.dependent_price_lists_prices.generate';

    private int $productsBatchSize = self::BUFFER_SIZE;

    public function __construct(
        private ManagerRegistry $doctrine,
        private ShardManager $shardManager
    ) {
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        if (isset($messageBody['baseJobId'])) {
            $baseJob = $this->doctrine->getRepository(Job::class)->find($messageBody['baseJobId']);

            return $baseJob->getName() . ':wave:' . $messageBody['level'];
        }

        return self::getName() . ':v' . $messageBody['version'];
    }

    public function setProductsBatchSize(int $productsBatchSize): void
    {
        $this->productsBatchSize = $productsBatchSize;
    }

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Job to generate prices for dependent price lists by prices version.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('sourcePriceListId')
            ->required()
            ->info('ID of the source Price List')
            ->allowedTypes('int');

        $resolver->define('version')
            ->info('Unique version that may be used to get changed prices or affected products')
            ->required()
            ->allowedTypes('int', 'null', 'string');

        $resolver->define('baseJobId')
            ->info('Job ID of the base job.')
            ->default(null)
            ->allowedTypes('null', 'string', 'int');

        $resolver->define('level')
            ->info('Level of the price list dependency')
            ->default(0)
            ->allowedTypes('int');

        $resolver->define('productBatches')
            ->info('Batches of Product IDs by products or version')
            ->default(null)
            ->allowedTypes('null')
            ->normalize(function (Options $options, $value): \Generator {
                if (isset($options['version'])) {
                    yield from $this->doctrine
                        ->getRepository(ProductPrice::class)
                        ->getProductsByPriceListAndVersion(
                            $this->shardManager,
                            $options['sourcePriceListId'],
                            $options['version'],
                            $this->productsBatchSize
                        );
                } else {
                    yield from [];
                }
            });
    }
}
