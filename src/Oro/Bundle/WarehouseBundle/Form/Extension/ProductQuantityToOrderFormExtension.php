<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_2\AddQuantityToOrderFields;

class ProductQuantityToOrderFormExtension extends AbstractTypeExtension
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
            AddQuantityToOrderFields::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.warehouse.fields.product.minimum_quantity_to_order.label',
                'value_options' => [
                    'constraints' => [new Decimal()],
                ],
            ]
        )->add(
            AddQuantityToOrderFields::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.warehouse.fields.product.maximum_quantity_to_order.label',
                'value_options' => [
                    'constraints' => [new Decimal()],
                ],
            ]
        );
    }
}
