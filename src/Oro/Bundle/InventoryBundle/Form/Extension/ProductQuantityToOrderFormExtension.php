<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Model\Inventory;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ProductQuantityToOrderFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $product = $builder->getData();
        // set category as default fallback
        if (!$product->getMinimumQuantityToOrder()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $product->setMinimumQuantityToOrder($entityFallback);
        }

        if (!$product->getMaximumQuantityToOrder()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $product->setMaximumQuantityToOrder($entityFallback);
        }

        $builder->add(
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.fields.product.minimum_quantity_to_order.label',
                'required' => false,
            ]
        )->add(
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.fields.product.maximum_quantity_to_order.label',
                'required' => false,
            ]
        );
    }
}
