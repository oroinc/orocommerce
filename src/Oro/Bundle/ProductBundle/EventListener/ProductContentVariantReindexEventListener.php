<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ProductContentVariantReindexEventListener
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $productIds = [];

        $this->collectProductIds($unitOfWork->getScheduledEntityInsertions(), $productIds, $unitOfWork);
        $this->collectProductIds($unitOfWork->getScheduledEntityUpdates(), $productIds, $unitOfWork);
        $this->collectProductIds($unitOfWork->getScheduledEntityDeletions(), $productIds, $unitOfWork);

        $this->triggerReindex($productIds);
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        if (!$event->getData() instanceof ContentNodeInterface) {
            return;
        }
        $productIds = [];

        /** @var ContentNodeInterface $contentNode */
        $contentNode = $event->getData();
        $this->collectProductIds($contentNode->getContentVariants(), $productIds);

        $this->triggerReindex($productIds);
    }

    /**
     * @param array|Collection $entities
     * @param array &$productIds
     * @param UnitOfWork $unitOfWork
     */
    private function collectProductIds($entities, array &$productIds, UnitOfWork $unitOfWork = null)
    {
        if (empty($entities)) {
            return;
        }

        foreach ($entities as $entity) {
            if (!$entity instanceof ContentVariantInterface
                || $entity->getType() !== ProductPageContentVariantType::TYPE
                || !$entity->getProductPageProduct()) {
                continue;
            }

            $this->addProduct($entity->getProductPageProduct(), $productIds);
            if ($unitOfWork) {
                $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);
                if (array_key_exists('product_page_product', $entityChangeSet)) {
                    $this->addProduct($entityChangeSet['product_page_product'][0], $productIds);
                    $this->addProduct($entityChangeSet['product_page_product'][1], $productIds);
                }
            }
        }
    }

    /**
     * @param array $productIds
     */
    private function triggerReindex(array $productIds)
    {
        if ($productIds) {
            $event = new ReindexationRequestEvent([Product::class], [], $productIds);
            $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
    }

    /**
     * @param Product $product
     * @param array &$productIds
     */
    private function addProduct(Product $product, array &$productIds)
    {
        $productId = $product->getId();
        if (!in_array($productId, $productIds, true)) {
            $productIds[] = $productId;
        }
    }
}
