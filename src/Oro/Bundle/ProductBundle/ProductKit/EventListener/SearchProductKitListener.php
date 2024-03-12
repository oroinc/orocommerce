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
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\BeforeIndexEntitiesEvent;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Component\DoctrineUtils\ORM\ChangedEntityGeneratorTrait;

/**
 * Schedule for index operation for the product kit when:
 * - ProductKitItemProduct, ProductKitItemLabel or ProductKitItem has changed,
 * - Product with type simple which related to Product with type kit has reindexed.
 */
class SearchProductKitListener implements OptionalListenerInterface
{
    use ChangedEntityGeneratorTrait;
    use OptionalListenerTrait;

    /** @var int[] */
    private array $productKitIds = [];

    public function __construct(
        private ObjectMapper $mapper,
        private IndexerInterface $indexer,
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

    public function postFlush(): void
    {
        if (!$this->enabled || !$this->productKitIds) {
            return;
        }

        $products = $this->getRepository(Product::class)->findBy(['id' => array_unique($this->productKitIds)]);
        $this->indexer->save($products);
    }

    public function beforeIndexEntities(BeforeIndexEntitiesEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $ids = [];
        foreach ($event->getEntities() as $entity) {
            if ($entity instanceof Product && $entity->isSimple()) {
                $ids[] = $entity->getId();
            }
        }

        $productKits = $this->getRepository(Product::class)->getProductKitsByProductIds($ids);
        foreach ($productKits as $productKit) {
            $event->addEntity($productKit);
        }
    }

    public function prepareEntityMapEvent(PrepareEntityMapEvent $event): void
    {
        if (!$this->isApplicablePrepareEntityMapEvent($event->getEntity())) {
            return;
        }

        /** @var Product $product */
        $product = $event->getEntity();
        $data = $event->getData();
        $allText = '';

        $this->processKitItemLabels($product, $allText);
        $this->processKitItemProducts($product, $allText);

        $data[Query::TYPE_TEXT][Indexer::TEXT_ALL_DATA_FIELD] = $allText;

        $event->setData($data);
    }

    public function onClear(): void
    {
        $this->productKitIds = [];
    }

    private function isApplicablePrepareEntityMapEvent(object $entity): bool
    {
        return $this->enabled && $entity instanceof Product && $entity->isKit();
    }

    private function populateProductIds(object $entity): void
    {
        if ($entity instanceof ProductKitItemProduct || $entity instanceof ProductKitItemLabel) {
            if ($entity->getKitItem()?->getProductKit()) {
                $this->productKitIds[] = $entity->getKitItem()->getProductKit()->getId();
            }
        } elseif ($entity instanceof ProductKitItem && $entity->getProductKit()) {
            $this->productKitIds[] = $entity->getProductKit()->getId();
        }
    }

    /**
     * Add KitItemProduct all_text data to all_text index of product kit
     */
    private function processKitItemProducts(Product $product, string &$allText): void
    {
        foreach ($product->getKitItems() as $kitItem) {
            foreach ($kitItem->getKitItemProducts() as $kitItemProduct) {
                $data = $this->mapper->mapObject($kitItemProduct->getProduct());
                if (isset($data[Query::TYPE_TEXT][Indexer::TEXT_ALL_DATA_FIELD])) {
                    $allText = $this->mapper->buildAllDataField(
                        $allText,
                        $data[Query::TYPE_TEXT][Indexer::TEXT_ALL_DATA_FIELD]
                    );
                }
            }
        }
    }

    /**
     * Add KitItem label to all_text index
     */
    private function processKitItemLabels(Product $product, string &$allText): void
    {
        foreach ($product->getKitItems() as $kitItem) {
            foreach ($kitItem->getLabels() as $label) {
                $allText = $this->mapper->buildAllDataField($allText, $label);
            }
        }
    }

    private function getRepository(string $class): ObjectRepository
    {
        return $this->registry->getRepository($class);
    }
}
