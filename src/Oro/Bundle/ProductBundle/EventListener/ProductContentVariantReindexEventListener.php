<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ContentNodeFieldsChangesAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\DoctrineUtils\ORM\ChangedEntityGeneratorTrait;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Triggers Product re-indexation when some fields of ContentNode entity changed.
 */
class ProductContentVariantReindexEventListener implements ContentNodeFieldsChangesAwareInterface
{
    use ChangedEntityGeneratorTrait;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var FieldUpdatesChecker */
    private $fieldUpdatesChecker;

    /** @var WebCatalogUsageProviderInterface */
    private $webCatalogUsageProvider;

    /** @var ProductCollectionVariantReindexMessageSendListener */
    private $messageSendListener;

    /**
     * List of fields of ContentNode that this class will listen to changes.
     * If any of fields have any changes, product reindexation will be triggered.
     *
     * @var array
     */
    protected $fieldsChangesListenTo = ['titles'];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FieldUpdatesChecker $fieldUpdatesChecker,
        ProductCollectionVariantReindexMessageSendListener $messageSendListener,
        WebCatalogUsageProviderInterface $webCatalogUsageProvider = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->webCatalogUsageProvider = $webCatalogUsageProvider;
        $this->fieldUpdatesChecker = $fieldUpdatesChecker;
        $this->messageSendListener = $messageSendListener;
    }

    /**
     * {@inheritdoc}
     */
    public function addField($fieldName)
    {
        if (!in_array($fieldName, $this->fieldsChangesListenTo, true)) {
            $this->fieldsChangesListenTo[] = $fieldName;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fieldsChangesListenTo;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $productIds = [];
        $websiteIds = [];

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->collectChangedProductIds([$entity], $productIds, $unitOfWork);
            $this->collectWebsiteIds([$entity], $websiteIds);
            $this->collectChangedFields($entity, $productIds, $websiteIds);
        }

        foreach ($this->getCreatedAndDeletedEntities($unitOfWork) as $entity) {
            $this->collectProductIds([$entity], $productIds);
            $this->collectWebsiteIds([$entity], $websiteIds);
        }

        $this->triggerReindex($productIds, $websiteIds);
    }

    /**
     * @param array|Collection $entities
     * @param array            $productIds
     */
    private function collectProductIds($entities, array &$productIds)
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof ContentVariantInterface
                || $entity->getType() !== ProductPageContentVariantType::TYPE
                || !$entity->getProductPageProduct()
            ) {
                continue;
            }

            $this->addProduct($entity->getProductPageProduct(), $productIds);
        }
    }

    /**
     * @param array|Collection $entities
     */
    private function sendProductCollectionForReindex($entities)
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof ContentVariantInterface
                || $entity->getType() !== ProductCollectionContentVariantType::TYPE
                || !$entity->getProductCollectionSegment()
            ) {
                continue;
            }

            $this->messageSendListener->scheduleSegment($entity->getProductCollectionSegment(), true);
        }
    }

    /**
     * @param array|Collection $entities
     * @param array            $productIds
     * @param UnitOfWork       $unitOfWork
     */
    private function collectChangedProductIds($entities, array &$productIds, UnitOfWork $unitOfWork)
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof ContentVariantInterface
                || $entity->getType() !== ProductPageContentVariantType::TYPE
                || !$entity->getProductPageProduct()
            ) {
                continue;
            }

            $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);
            if (!array_key_exists('product_page_product', $entityChangeSet)) {
                continue;
            }

            foreach ($entityChangeSet['product_page_product'] as $product) {
                if (!empty($product)) {
                    $this->addProduct($product, $productIds);
                }
            }
        }
    }

    /**
     * @param array|Collection $entities
     * @param array|null $websitesId
     */
    private function collectWebsiteIds($entities, &$websitesId)
    {
        if ($this->webCatalogUsageProvider === null) {
            return;
        }

        $assignedWebCatalogs = $this->webCatalogUsageProvider->getAssignedWebCatalogs();

        if (count($assignedWebCatalogs) === 0) {
            return;
        }

        foreach ($entities as $entity) {
            if (!$this->isValidContentVariantEntity($entity)) {
                continue;
            }
            $webCatalogId = $entity->getNode()->getWebCatalog()->getId();
            // filter for only those websites which have current `$webCatalogId` assigned
            $relatedWebsiteIds = array_filter(
                $assignedWebCatalogs,
                function ($relatedWebsiteWebCatalogId) use ($webCatalogId) {
                    return $webCatalogId == $relatedWebsiteWebCatalogId;
                }
            );
            if (!empty($relatedWebsiteIds)) {
                $websitesId = array_unique(array_merge($websitesId, array_keys($relatedWebsiteIds)));
            }
        }
    }

    /**
     * @param mixed $entity
     * @param array $productIds
     * @param array $websiteIds
     */
    private function collectChangedFields($entity, array &$productIds, array &$websiteIds)
    {
        $isAnyFieldChanged = false;

        if (!$entity instanceof ContentNodeInterface) {
            return;
        }

        foreach ($this->getFields() as $fieldName) {
            if ($this->fieldUpdatesChecker->isRelationFieldChanged($entity, $fieldName)) {
                $isAnyFieldChanged = true;
                break;
            }
        }

        // if any of configurable field of ContentNode has changed - reindex all products related to it
        if ($isAnyFieldChanged) {
            $this->collectProductIds($entity->getContentVariants(), $productIds);
            $this->collectWebsiteIds($entity->getContentVariants(), $websiteIds);
            $this->sendProductCollectionForReindex($entity->getContentVariants());
        }
    }

    /**
     * @param mixed $entity
     * @return bool
     */
    private function isValidContentVariantEntity($entity)
    {
        if (!$entity instanceof ContentVariantInterface
            || !$entity instanceof ContentNodeAwareInterface
            || $entity->getType() !== ProductPageContentVariantType::TYPE) {
            return false;
        }

        $contentNode = $entity->getNode();

        if (!$contentNode instanceof WebCatalogAwareInterface) {
            return false;
        }

        return true;
    }

    private function triggerReindex(array $productIds, array $websiteIds)
    {
        if (count($productIds) === 0 || count($websiteIds) === 0) {
            return;
        }

        $event = new ReindexationRequestEvent([Product::class], $websiteIds, $productIds, true, ['main']);
        $this->eventDispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }

    private function addProduct(Product $product, array &$productIds)
    {
        $productId = $product->getId();
        if (!in_array($productId, $productIds, true)) {
            $productIds[] = $productId;
        }
    }
}
