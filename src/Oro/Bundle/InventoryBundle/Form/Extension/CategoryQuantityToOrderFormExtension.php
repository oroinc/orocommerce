<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Model\Inventory;

class CategoryQuantityToOrderFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $category = $builder->getData();
        //set system config as default fallback
        if (!$category->getMinimumQuantityToOrder()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
            $category->setMinimumQuantityToOrder($entityFallback);
        }

        if (!$category->getMaximumQuantityToOrder()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
            $category->setMaximumQuantityToOrder($entityFallback);
        }

        $builder->add(
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.inventory.fields.category.minimum_quantity_to_order.label',
                'required' => false,
            ]
        )->add(
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.inventory.fields.category.maximum_quantity_to_order.label',
                'required' => false,
            ]
        );
    }
}
