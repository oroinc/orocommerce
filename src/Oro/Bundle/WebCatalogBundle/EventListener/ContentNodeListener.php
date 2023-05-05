<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Handler\CollectionSortOrderHandler;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Resolve content node slugs on entity create, remove or fields update
 */
class ContentNodeListener
{
    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var ResolveNodeSlugsMessageFactory
     */
    protected $messageFactory;

    /**
     * @var CollectionSortOrderHandler
     */
    protected $collectionSortOrderHandler;

    public function __construct(
        ContentNodeMaterializedPathModifier $modifier,
        ExtraActionEntityStorageInterface $storage,
        MessageProducerInterface $messageProducer,
        ResolveNodeSlugsMessageFactory $messageFactory,
        CollectionSortOrderHandler $collectionSortOrderHandler
    ) {
        $this->modifier = $modifier;
        $this->storage = $storage;
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
        $this->collectionSortOrderHandler = $collectionSortOrderHandler;
    }

    public function postPersist(ContentNode $contentNode)
    {
        $contentNode = $this->modifier->calculateMaterializedPath($contentNode);
        $this->storage->scheduleForExtraInsert($contentNode);
    }

    public function preUpdate(ContentNode $contentNode, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();

        if (!empty($changeSet[ContentNode::FIELD_PARENT_NODE])) {
            $this->modifier->calculateMaterializedPath($contentNode);
            $childNodes = $this->modifier->calculateChildrenMaterializedPath($contentNode);

            $this->storage->scheduleForExtraInsert($contentNode);
            foreach ($childNodes as $childNode) {
                $this->storage->scheduleForExtraInsert($childNode);
            }
        }
    }

    public function postRemove(ContentNode $contentNode, LifecycleEventArgs $args)
    {
        if ($contentNode->getParentNode() && $contentNode->getParentNode()->getId()) {
            if (!$args->getObjectManager()->getUnitOfWork()->isScheduledForDelete($contentNode->getParentNode())) {
                $this->scheduleContentNodeRecalculation($contentNode->getParentNode());
            }
        } else {
            $this->messageProducer->send(WebCatalogCalculateCacheTopic::getName(), [
                WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $contentNode->getWebCatalog()->getId(),
            ]);
        }
    }

    /**
     * Form after flush is used to catch all content node fields update, including
     * - collections of localized fallback values which are used for Titles and Slug Prototypes.
     * - new sort order values for Products in Segments for ProductCollection Variants.
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        $this->saveCollectionVariantsSortOrders($event);

        $this->scheduleContentNodeRecalculation($event->getData());
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

    protected function scheduleContentNodeRecalculation(ContentNode $contentNode)
    {
        $this->messageProducer->send(
            WebCatalogResolveContentNodeSlugsTopic::getName(),
            $this->messageFactory->createMessage($contentNode)
        );
    }
}
