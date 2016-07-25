<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

/**
 * Regular extension is used to change default set of units used to render and validate data
 */
class ProductPriceUnitSelectorType extends ProductUnitSelectionType
{
    const NAME = 'orob2b_pricing_product_price_unit_selector';

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
