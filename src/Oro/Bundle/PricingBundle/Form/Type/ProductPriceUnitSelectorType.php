<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Symfony\Component\Form\FormInterface;

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
        $productForm = $this->getProductForm($form);
        if (!$productForm ||
            !$productForm->has('primaryUnitPrecision') ||
            !$productForm->has('additionalUnitPrecisions')
        ) {
            return parent::getProductUnits($form, $product);
        }

        /** @var ProductUnitPrecision $primaryUnitPrecision */
        $primaryUnitPrecision = $productForm->get('primaryUnitPrecision')->getData();

        /** @var ProductUnitPrecision[] $additionalUnitPrecisions */
        $additionalUnitPrecisions = $productForm->get('additionalUnitPrecisions')->getData();

        return $this->getAllProductEnabledUnits($primaryUnitPrecision, $additionalUnitPrecisions);
    }

    /**
     * @param ProductUnitPrecision|null $primaryUnitPrecision
     * @param ProductUnitPrecision[] $additionalUnitPrecisions
     * @return ProductUnit[]
     */
    protected function getAllProductEnabledUnits(
        ProductUnitPrecision $primaryUnitPrecision = null,
        $additionalUnitPrecisions
    ) {
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

    /**
     * @param FormInterface $form
     * @return null|FormInterface
     */
    protected function getProductForm(FormInterface $form)
    {
        $priceType = $form->getParent();
        $collectionForm = $priceType ? $priceType->getParent() : null;

        return $collectionForm ? $collectionForm->getParent() : null;
    }
}
