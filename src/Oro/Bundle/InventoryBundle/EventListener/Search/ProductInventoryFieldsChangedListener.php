<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValueRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;

/**
 * Schedules re-indexation of a product when a value of the following EntityFieldFallbackValue fields was changed:
 * * lowInventoryThreshold
 * * highlightLowInventory
 * * isUpcoming
 */
class ProductInventoryFieldsChangedListener
{
    private const PRODUCT_FIELD_NAMES = [
        'lowInventoryThreshold',
        'highlightLowInventory',
        'isUpcoming'
    ];
    private const CATEGORY_FIELD_NAMES = [
        'lowInventoryThreshold',
        'highlightLowInventory',
        'isUpcoming'
    ];
    private const CONFIG_OPTION_NAMES = [
        'oro_inventory.low_inventory_threshold',
        'oro_inventory.highlight_low_inventory'
    ];

    private IndexationEntitiesContainer $changedEntities;
    private ProductReindexManager $productReindexManager;

    public function __construct(
        IndexationEntitiesContainer $changedEntities,
        ProductReindexManager $productReindexManager
    ) {
        $this->changedEntities = $changedEntities;
        $this->productReindexManager = $productReindexManager;
    }

    public function postUpdate(EntityFieldFallbackValue $value, LifecycleEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $valueId = $value->getId();
        $productId = $this->findProductId($em, $valueId);
        if (null !== $productId) {
            $this->scheduleProductReindex($em, $productId);
        } else {
            $categoryId = $this->findCategoryId($em, $valueId);
            if (null !== $categoryId) {
                $this->scheduleCategoryReindex($em, $categoryId);
            }
        }
    }

    public function onConfigUpdate(ConfigUpdateEvent $args): void
    {
        if ($this->hasChangedConfigOptions($args)) {
            $websiteId = null;
            if ('website' === $args->getScope()) {
                $websiteId = $args->getScopeId();
            }
            $this->productReindexManager->reindexAllProducts($websiteId, true, ['inventory']);
        }
    }

    private function scheduleProductReindex(EntityManagerInterface $em, int $productId): void
    {
        $this->changedEntities->addEntity($em->getReference(Product::class, $productId));
    }

    private function scheduleCategoryReindex(EntityManagerInterface $em, int $categoryId): void
    {
        $productIds = $this->getCategoryRepository($em)->getProductIdsByCategories(
            [$em->getReference(Category::class, $categoryId)]
        );
        foreach ($productIds as $productId) {
            $this->scheduleProductReindex($em, $productId);
        }
    }

    private function hasChangedConfigOptions(ConfigUpdateEvent $args): bool
    {
        foreach (self::CONFIG_OPTION_NAMES as $configOptionName) {
            if ($args->isChanged($configOptionName)) {
                return true;
            }
        }

        return false;
    }

    private function findProductId(EntityManagerInterface $em, int $valueId): ?int
    {
        return $this->findEntityId($em, Product::class, $valueId, self::PRODUCT_FIELD_NAMES);
    }

    private function findCategoryId(EntityManagerInterface $em, int $valueId): ?int
    {
        return $this->findEntityId($em, Category::class, $valueId, self::CATEGORY_FIELD_NAMES);
    }

    private function findEntityId(
        EntityManagerInterface $em,
        string $entityClass,
        int $valueId,
        array $fieldNames
    ): ?int {
        foreach ($fieldNames as $fieldName) {
            $entityId = $this->getEntityFieldFallbackValueRepository($em)
                ->findEntityId($entityClass, $fieldName, $valueId);
            if (null !== $entityId) {
                return $entityId;
            }
        }

        return null;
    }

    private function getEntityFieldFallbackValueRepository(
        EntityManagerInterface $em
    ): EntityFieldFallbackValueRepository {
        return $em->getRepository(EntityFieldFallbackValue::class);
    }

    private function getCategoryRepository(EntityManagerInterface $em): CategoryRepository
    {
        return $em->getRepository(Category::class);
    }
}
