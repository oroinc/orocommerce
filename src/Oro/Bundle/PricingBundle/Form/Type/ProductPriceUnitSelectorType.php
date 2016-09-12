<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

/**
 * Regular extension is used to change default set of units used to render and validate data
 */
class ProductPriceUnitSelectorType extends ProductUnitSelectionType
{
    const NAME = 'oro_pricing_product_price_unit_selector';

    /**
     * @param FormInterface $form
     * @param Product|null $product
     * @return ProductUnit[]
     */
    protected function getProductUnits(FormInterface $form, Product $product = null)
    {
        $priceType = $form->getParent();
        if (!$priceType) {
            return parent::getProductUnits($form, $product);
        }
        $collectionForm = $priceType->getParent();
        if (!$collectionForm) {
            return parent::getProductUnits($form, $product);
        }
        $productForm = $collectionForm->getParent();
        if (!$productForm ||
            !$productForm->has('primaryUnitPrecision') ||
            !$productForm->has('additionalUnitPrecisions')) {
            return parent::getProductUnits($form, $product);
        }

        /** @var ProductUnitPrecision $primaryUnitPrecision */
        $primaryUnitPrecision = $productForm->get('primaryUnitPrecision')->getData();

        /** @var ProductUnitPrecision[] $additionalUnitPrecisions */
        $additionalUnitPrecisions = $productForm->get('additionalUnitPrecisions')->getData();
        $units = [];
        if ($primaryUnitPrecision) {
            $units[] = $primaryUnitPrecision->getUnit();
        }
        if ($additionalUnitPrecisions) {
            foreach ($additionalUnitPrecisions as $precision) {
                $units[] = $precision->getUnit();
            }
        }

        return $units;
    }
}
