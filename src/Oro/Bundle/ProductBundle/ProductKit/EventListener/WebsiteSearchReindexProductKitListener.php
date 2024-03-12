<?php

namespace Oro\Bundle\ProductBundle\ProductKit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\DoctrineUtils\ORM\ChangedEntityGeneratorTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Schedule for reindex the product kit when:
 * - ProductKitItemProduct, ProductKitItemLabel or ProductKitItem has changed,
 * - Product with type simple which related to Product with type kit has reindexed.
 */
class WebsiteSearchReindexProductKitListener implements OptionalListenerInterface
{
    use ChangedEntityGeneratorTrait;
    use OptionalListenerTrait;

    /** @var int[] */
    private array $cachedIds = [];

    public function __construct(
        private IndexationEntitiesContainer $entitiesContainer,
        private EventDispatcherInterface $eventDispatcher,
        private ManagerRegistry $registry
    ) {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if (!$this->enabled) {
            return;
        }

        $uow = $eventArgs->getObjectManager()->getUnitOfWork();
        foreach ($this->getChangedEntities($uow) as $entity) {
            $this->populateProductIds($entity);
        }
    }

    public function onReindexationRequest(ReindexationRequestEvent $event): void
    {
        if (!$this->isApplicableReindexationRequest($event)) {
            return;
        }

        $ids = $this->getRepository(Product::class)->getProductKitIdsByProductIds($event->getIds());
        if (array_diff($ids, $event->getIds())) {
            $this->cachedIds = $ids;
            $this->dispatch($event);
        }
    }

    public function onClear(): void
    {
        $this->cachedIds = [];
    }

    private function isApplicableReindexationRequest(ReindexationRequestEvent $event): bool
    {
        return $this->enabled &&
            in_array(Product::class, $event->getClassesNames(), true) &&
            $event->getIds() !== $this->cachedIds;
    }

    private function dispatch(ReindexationRequestEvent $event): void
    {
        $newEvent = new ReindexationRequestEvent(
            $event->getClassesNames(),
            $event->getWebsitesIds(),
            $this->cachedIds,
            $event->isScheduled(),
            $event->getFieldGroups()
        );
        $this->eventDispatcher->dispatch($newEvent, ReindexationRequestEvent::EVENT_NAME);
    }

    private function populateProductIds(object $entity): void
    {
        if ($entity instanceof ProductKitItemProduct || $entity instanceof ProductKitItemLabel) {
            if ($entity->getKitItem()?->getProductKit()) {
                $this->entitiesContainer->addEntity($entity->getKitItem()->getProductKit());
            }
        } elseif ($entity instanceof ProductKitItem && $entity->getProductKit()) {
            $this->entitiesContainer->addEntity($entity->getProductKit());
        }
    }

    private function getRepository(string $class): ObjectRepository
    {
        return $this->registry->getRepository($class);
    }
}
