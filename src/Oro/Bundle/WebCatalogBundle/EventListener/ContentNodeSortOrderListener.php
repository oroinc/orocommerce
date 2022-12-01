<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Handler\CollectionSortOrderHandler;

/**
 * Resolve content node sort orders on entity create, remove or fields update
 */
class ContentNodeSortOrderListener
{
    /**
     * @var CollectionSortOrderHandler
     */
    protected $collectionSortOrderHandler;

    public function __construct(
        CollectionSortOrderHandler $collectionSortOrderHandler
    ) {
        $this->collectionSortOrderHandler = $collectionSortOrderHandler;
    }

    /**
     * Form after flush is used to catch all content node fields update, related to
     * new sort order values for Products in Segments for ProductCollection Variants.
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        $this->saveCollectionVariantsSortOrders($event);
    }

    /**
     * Depending on the form submitted, if there are ContentVariants that are of ProductCollection type
     * we get all unsaved CollectionSortOrder values (happens when either the ContentVariant or the CollectionSortOrder
     * is new) and save them through a specific handler
     */
    protected function saveCollectionVariantsSortOrders(AfterFormProcessEvent $event): void
    {
        $sortOrdersToUpdate = [];
        foreach ($event->getForm()->get('contentVariants') as $contentVariantForm) {
            if ($contentVariantForm->has('productCollectionSegment')) {
                $productCollectionSegmentForm = $contentVariantForm->get('productCollectionSegment');
                $segment = $contentVariantForm->get('productCollectionSegment')->getData();
                $sortOrdersToUpdate[$segment->getId()]['segment'] = $segment;
                if ($productCollectionSegmentForm->has('sortOrder')) {
                    $collectionSortOrderForm = $productCollectionSegmentForm->get('sortOrder');
                    if (!is_null($collectionSortOrderForm->getData())) {
                        foreach ($collectionSortOrderForm->getData() as $collectionSortOrderToProcess) {
                            $sortOrdersToUpdate[$segment->getId()]['sortOrders'][] =
                                $collectionSortOrderToProcess['data'];
                        }
                    }
                }
            }
        }
        $this->collectionSortOrderHandler->updateCollections($sortOrdersToUpdate);
    }
}
