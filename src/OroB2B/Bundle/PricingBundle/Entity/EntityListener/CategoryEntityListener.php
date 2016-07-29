<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * Handle category scalar attributes changes, category parent change, category remove.
 * Handle add/remove of products.
 * Add price rule recalculation trigger if necessary.
 */
class CategoryEntityListener extends AbstractRuleEntityListener
{
    const FIELD_PARENT_CATEGORY = 'parentCategory';
    const FIELD_PRODUCTS = 'products';

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(self::FIELD_PARENT_CATEGORY)) {
            // handle category tree changes
            $this->recalculateByEntity();
        } else {
            $this->recalculateByEntityFieldsUpdate($event->getEntityChangeSet());
        }
    }

    public function preRemove()
    {
        $this->recalculateByEntity();
    }

    /**
     * @param Category $category
     * @param PreFlushEventArgs $event
     */
    public function preFlush(Category $category, PreFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $collections = $unitOfWork->getScheduledCollectionUpdates();
        foreach ($collections as $collection) {
            if ($collection instanceof PersistentCollection && $collection->getOwner() instanceof Category
                && $collection->getMapping()['fieldName'] === self::FIELD_PRODUCTS
                && $collection->isDirty() && $collection->isInitialized()
            ) {
                // Get lexemes associated with Category::id relation
                $lexemes = $this->findEntityLexemes(['id']);
                /** @var Product $product */
                foreach (array_merge($collection->getInsertDiff(), $collection->getDeleteDiff()) as $product) {
                    $this->addTriggersByLexemes($lexemes, $product);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return Category::class;
    }
}
