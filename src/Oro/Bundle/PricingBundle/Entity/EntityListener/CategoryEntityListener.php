<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

/**
 * Handle category scalar attributes changes, category parent change, category remove.
 * Handle add/remove of products.
 * Add price rule recalculation trigger if necessary.
 */
class CategoryEntityListener extends AbstractRuleEntityListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if ($event->hasChangedField(Category::FIELD_PARENT_CATEGORY)) {
            // handle category tree changes
            $this->recalculateByEntity();
        } else {
            $this->recalculateByEntityFieldsUpdate($event->getEntityChangeSet());
        }
    }

    public function preRemove()
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $this->recalculateByEntity();
    }

    public function onProductsChangeRelation(ProductsChangeRelationEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $products = $event->getProducts();
        // Get lexemes associated with Category::id relation
        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
            $this->getEntityClassName(),
            ['id']
        );

        $this->priceRuleLexemeTriggerHandler->processLexemes($lexemes, $products);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return Category::class;
    }
}
