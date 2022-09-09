<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 */
class CombineSingleCombinedPriceListPricesTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.price_lists.cpl.rebuild.single';

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
        return 'Combine prices for active and ready to rebuild Combined Price List for a given list of price lists ' .
            'and products.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('jobId')
            ->info('Job ID of parent unique job.')
            ->required()
            ->allowedTypes('int', 'string');

        $resolver->define('collection')
            ->info(
                'Normalized Price List Relations collection that will be used for corresponding ' .
                'Combined Price List search/creation'
            )
            ->allowedTypes('array')
            ->default(function (OptionsResolver $collectionResolver) {
                $collectionResolver->setPrototype(true);

                $collectionResolver->define('p')
                    ->info('Price List ID')
                    ->required()
                    ->allowedTypes('int');

                $collectionResolver->define('m')
                    ->info('Merge flag value')
                    ->default(true)
                    ->allowedTypes('bool');
            });

        $resolver->define('products')
            ->info('Collection of Product IDs for which combined prices should be rebuilt.')
            ->default([])
            ->allowedTypes('int[]', 'string[]');

        $resolver->define('assign_to')
            ->info('A list of relations to which Combined Price List will be assigned after build.')
            ->default([])
            ->allowedTypes('array');

        $resolver
            ->define('version')
            ->info('Assignment version. Used to prevent assignment overriding if newer version exists.')
            ->default(null)
            ->allowedTypes('int', 'null')
            ->normalize(fn (Options $options, $value) => $options['assign_to'] ? $value : null);

        $resolver->define('cpl')
            ->info(
                'ID (optional) of existing Combined Price List for which combined prices should be rebuilt.'
                . 'May either be resolved as CombinedPriceList instance when CPL found,'
                . ' null when CPL not found,'
                . ' and false when there was retryable error during CPL fetch/creation.'
            )
            ->default(null)
            ->allowedTypes('int', 'null')
            ->normalize(function (Options $options, $value): CombinedPriceList|bool|null {
                if ($value) {
                    return $this->combinedPriceListProvider->getCombinedPriceListById($value);
                }

                if (isset($options['collection'])) {
                    try {
                        return $this->combinedPriceListProvider->getCombinedPriceListByCollectionInformation(
                            $options['collection']
                        );
                    } catch (RetryableException|ForeignKeyConstraintViolationException $e) {
                        return false;
                    } catch (EntityNotFoundException $e) {
                        // CPL cannot be retrieved if any of price lists in the chain do not exist.
                        return null;
                    }
                }

                return null;
            });
    }
}
