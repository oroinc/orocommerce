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

    /**
     * @param ProductCollectionVariantReindexMessageSendListener $reindexEventListener
     */
    public function __construct(ProductCollectionVariantReindexMessageSendListener $reindexEventListener)
    {
        $this->reindexEventListener = $reindexEventListener;
    }

    /**
     * @param ProductCollectionDefinitionConverter $productCollectionDefinitionConverter
     */
    public function setConverter(ProductCollectionDefinitionConverter $productCollectionDefinitionConverter)
    {
        $this->productCollectionDefinitionConverter = $productCollectionDefinitionConverter;
    }

    /**
     * @param Segment $segment
     */
    public function postPersist(Segment $segment)
    {
        $this->reindexEventListener->scheduleSegment($segment);
    }

    /**
     * @param Segment $segment
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Segment $segment, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('definition')) {
            $oldIncludedIds = $this->getIncludedIds($args->getOldValue('definition'));
            $newIncludedIds = $this->getIncludedIds($args->getNewValue('definition'));
            $additionalProducts = array_merge(
                array_diff($oldIncludedIds, $newIncludedIds),
                array_diff($newIncludedIds, $oldIncludedIds)
            );

            $this->reindexEventListener->scheduleSegment($segment, false);
            $this->reindexEventListener->scheduleAdditionalProductsBySegment($segment, $additionalProducts);
        }
    }

    /**
     * @param Segment $segment
     */
    public function preRemove(Segment $segment)
    {
        $this->reindexEventListener->scheduleMessageBySegmentDefinition($segment);
    }

    /**
     * @param string $definition
     * @return array
     */
    protected function getIncludedIds(string $definition): array
    {
        $definitionParts = $this->productCollectionDefinitionConverter->getDefinitionParts($definition);

        return array_filter(array_map(
            'intval',
            explode(',', $definitionParts[ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY])
        ));
    }
}
