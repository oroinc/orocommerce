<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

class ProductUnitFieldsSettingsExtension extends \Twig_Extension
{
    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    protected $productUnitFieldsSettings;

    /**
     * @param ProductUnitFieldsSettingsInterface $productUnitFieldsSettings
     */
    public function __construct(ProductUnitFieldsSettingsInterface $productUnitFieldsSettings)
    {
        $this->productUnitFieldsSettings = $productUnitFieldsSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_is_product_unit_selection_visible',
                [$this->productUnitFieldsSettings, 'isProductUnitSelectionVisible']
            ),
            new \Twig_SimpleFunction(
                'oro_is_product_primary_unit_visible',
                [$this->productUnitFieldsSettings, 'isProductPrimaryUnitVisible']
            ),
            new \Twig_SimpleFunction(
                'oro_is_adding_additional_units_to_product_available',
                [$this->productUnitFieldsSettings, 'isAddingAdditionalUnitsToProductAvailable']
            ),
        ];
    }
}
