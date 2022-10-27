<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRange;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * This extension adds 'lowInventoryThreshold' field to product form
 */
class ProductLowInventoryThresholdFormExtension extends AbstractTypeExtension
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
        if (!$product->getLowInventoryThreshold()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
            $product->setLowInventoryThreshold($entityFallback);
        }

        $builder->add(
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.low_inventory_threshold.label',
                'required' => false,
                'value_options' => [
                    // Here we overwrite settings from system_configuration.yml
                    // for oro_inventory.low_inventory_threshold
                    // because this value can be blank in case of product.
                    // Also constraints are needed both here and in validation.yml to make frontend validation work.
                    'constraints' => [new NumericRange(['min' => -100000000, 'max' => 100000000])]
                ]
            ]
        );
    }
}
