<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 */
class CombineSingleCombinedPriceListPricesTopic implements TopicInterface
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

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        // Job ID of parent unique job.
        $resolver->setRequired('jobId');
        $resolver->setAllowedTypes('jobId', ['int', 'string']);

        // Normalized Price List Relations collection that will be used for corresponding
        // Combined Price List search/creation
        $resolver->setDefined('collection');
        $resolver->setAllowedTypes('collection', 'array');

        // Collection of Product IDs for which combined prices should be rebuilt.
        $resolver->setDefault('products', []);
        $resolver->setAllowedTypes('collection', 'array');

        // A list of relations to which Combined Price List will be assigned after build.
        $resolver->setDefined('assign_to');
        $resolver->setDefault('assign_to', []);
        $resolver->setAllowedTypes('assign_to', 'array');

        // ID of existing Combined Price List for which combined prices should be rebuilt.
        $resolver->setDefault('cpl', null);
        $resolver->setAllowedTypes('cpl', ['int', 'null']);
        $resolver->setNormalizer(
            'cpl',
            function (Options $options, $value): ?CombinedPriceList {
                if ($value) {
                    return $this->combinedPriceListProvider->getCombinedPriceListById($value);
                }

                if (isset($options['collection'])) {
                    try {
                        return $this->combinedPriceListProvider->getCombinedPriceListByCollectionInformation(
                            $options['collection']
                        );
                    } catch (EntityNotFoundException $e) {
                        // CPL cannot be retrieved if any of price lists in the chain do not exist.
                        return null;
                    }
                }

                return null;
            }
        );
    }
}
