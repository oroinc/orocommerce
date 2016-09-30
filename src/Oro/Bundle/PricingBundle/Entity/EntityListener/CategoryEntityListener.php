<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;

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
     * @param ProductsChangeRelationEvent $event
     */
    public function onProductsChangeRelation(ProductsChangeRelationEvent $event)
    {
        $products = $event->getProducts();
        // Get lexemes associated with Category::id relation
        $lexemes = $this->findEntityLexemes(['id']);

        foreach ($products as $product) {
            $this->addTriggersByLexemes($lexemes, $product);
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
