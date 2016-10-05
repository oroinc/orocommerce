<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Handle category scalar attributes changes, category parent change, category remove.
 * Handle add/remove of products.
 * Add price rule recalculation trigger if necessary.
 */
class CategoryEntityListener extends AbstractRuleEntityListener
{
    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(Category::FIELD_PARENT_CATEGORY)) {
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
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $collections = $unitOfWork->getScheduledCollectionUpdates();
        foreach ($collections as $collection) {
            if ($collection instanceof PersistentCollection && $collection->getOwner() instanceof Category
                && $collection->getMapping()['fieldName'] === Category::FIELD_PRODUCTS
                && $collection->isDirty() && $collection->isInitialized()
            ) {
                // Get lexemes associated with Category::id relation
                $lexemes = $this->priceRuleLexemeTriggerHandler
                    ->findEntityLexemes($this->getEntityClassName(), ['id']);
                /** @var Product $product */
                foreach (array_merge($collection->getInsertDiff(), $collection->getDeleteDiff()) as $product) {
                    $this->priceRuleLexemeTriggerHandler->addTriggersByLexemes($lexemes, $product);
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
