<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\WarehouseBundle\Fallback\Provider\CategoryFallbackProvider;

class WarehouseProductTypeFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $product = $builder->getData();
        // set category as default fallback
        if (!$product->getManageInventory()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setUseFallback(true)
                ->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $product->setManageInventory($entityFallback);
        }

        $builder->add(
            'manageInventory',
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.warehouse.manage_inventory.label',
                'fallback_translation_prefix' => 'oro.warehouse.fallback',
            ]
        );
    }
}
