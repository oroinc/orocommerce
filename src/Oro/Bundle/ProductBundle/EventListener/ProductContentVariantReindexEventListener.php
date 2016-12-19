<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    protected $eventDispatcher;

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

        $productIds = $this->collectProductIds($unitOfWork->getScheduledEntityInsertions(), $productIds);
        $productIds = $this->collectProductIds($unitOfWork->getScheduledEntityUpdates(), $productIds);
        $productIds = $this->collectProductIds($unitOfWork->getScheduledEntityDeletions(), $productIds);

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

        /** @var ContentNodeInterface $contentNode */
        $contentNode = $event->getData();
        $productIds = $this->collectProductIds($contentNode->getContentVariants(), []);

        $this->triggerReindex($productIds);
    }

    /**
     * @param array|Collection $entities
     * @param array $productIds
     * @return array
     */
    protected function collectProductIds($entities, array $productIds)
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof ContentVariantInterface
                || $entity->getType() !== ProductPageContentVariantType::TYPE) {
                continue;
            }

            if (!$entity->getProductPageProduct()) {
                continue;
            }

            /** @var Product $product */
            $product = $entity->getProductPageProduct();
            if ($product->getId() && !in_array($product->getId(), $productIds, true)) {
                $productIds[] = $product->getId();
            }
        }

        return $productIds;
    }

    /**
     * @param array $productIds
     */
    protected function triggerReindex(array $productIds)
    {
        if ($productIds) {
            $event = new ReindexationRequestEvent([Product::class], [], $productIds);
            $this->eventDispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
    }
}
