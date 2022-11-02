<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * This listener is used to track segment entity's creation, remove
 * and definition change to schedule it for re-indexation
 */
class ProductCollectionAwareContentVariantEntityListener
{
    /**
     * @var ProductCollectionVariantReindexMessageSendListener
     */
    private $reindexEventListener;

    /**
     * @var ProductCollectionDefinitionConverter
     */
    private $productCollectionDefinitionConverter;

    public function __construct(
        ProductCollectionVariantReindexMessageSendListener $reindexEventListener,
        ProductCollectionDefinitionConverter $productCollectionDefinitionConverter
    ) {
        $this->reindexEventListener = $reindexEventListener;
        $this->productCollectionDefinitionConverter = $productCollectionDefinitionConverter;
    }

    public function postPersist(Segment $segment)
    {
        $this->reindexEventListener->scheduleSegment($segment);
    }

    public function preUpdate(Segment $segment, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('definition')) {
            $oldIncludedIds = $this->getIncludedIds($args->getOldValue('definition'));
            $newIncludedIds = $this->getIncludedIds($args->getNewValue('definition'));
            $additionalProducts = array_merge(
                array_diff($oldIncludedIds, $newIncludedIds),
                array_diff($newIncludedIds, $oldIncludedIds)
            );

            $this->reindexEventListener->scheduleSegment($segment, false, $additionalProducts);
        }
    }

    public function preRemove(Segment $segment)
    {
        $this->reindexEventListener->scheduleMessageBySegmentDefinition($segment);
    }

    protected function getIncludedIds(string $definition): array
    {
        $definitionParts = $this->productCollectionDefinitionConverter->getDefinitionParts($definition);

        return array_filter(array_map(
            'intval',
            explode(',', $definitionParts[ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY])
        ));
    }
}
