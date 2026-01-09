<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Extends the product form to add low inventory highlighting field with category fallback support.
 */
class ProductHighlightLowInventoryFormExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $builder->getData();
        // set category as default fallback
        if (!$product->getHighlightLowInventory()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $product->setHighlightLowInventory($entityFallback);
        }

        $builder->add(
            LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.highlight_low_inventory.label',
            ]
        );
    }
}
