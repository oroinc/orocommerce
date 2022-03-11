<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for collecting Price list collections by Config
 */
class CollectByConfigEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.assignment.collect.by_config';

    private array $combinedPriceListAssociations = [];
    private bool $includeSelfFallback;
    private bool $collectOnCurrentLevel;

    public function __construct(bool $includeSelfFallback = false, bool $collectOnCurrentLevel = true)
    {
        $this->includeSelfFallback = $includeSelfFallback;
        $this->collectOnCurrentLevel = $collectOnCurrentLevel;
    }

    public function addAssociation(array $collectionInfo, array $associateWith): void
    {
        $identifier = $collectionInfo['identifier'];
        if (!array_key_exists($identifier, $this->combinedPriceListAssociations)) {
            $this->combinedPriceListAssociations[$identifier] = ['collection' => $collectionInfo['elements']];
        }

        $this->combinedPriceListAssociations[$identifier]['assign_to'] = ArrayUtil::arrayMergeRecursiveDistinct(
            $this->combinedPriceListAssociations[$identifier]['assign_to'] ?? [],
            $associateWith
        );
    }

    public function getCombinedPriceListAssociations(): array
    {
        return $this->combinedPriceListAssociations;
    }

    public function mergeAssociations(CollectByConfigEvent $event): void
    {
        foreach ($event->getCombinedPriceListAssociations() as $identifier => $data) {
            $this->addAssociation(
                ['identifier' => $identifier, 'elements' => $data['collection']],
                $data['assign_to']
            );
        }
    }

    public function isIncludeSelfFallback(): bool
    {
        return $this->includeSelfFallback;
    }


    public function isCollectOnCurrentLevel(): bool
    {
        return $this->collectOnCurrentLevel;
    }

    public function addConfigAssociation(array $collectionInfo): void
    {
        $this->addAssociation($collectionInfo, ['config' => true]);
    }
}
