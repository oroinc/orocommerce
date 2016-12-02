<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Regular extension is used to change default set of units used to render and validate data
 */
class ProductPriceUnitSelectorType extends ProductUnitSelectionType
{
    const NAME = 'oro_pricing_product_price_unit_selector';

    /**
     * @var SingleUnitModeService
     */
    protected $singleUnitModeService;

    /**
     * @var SystemDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    /**
     * @param ProductUnitLabelFormatter $productUnitFormatter
     * @param TranslatorInterface $translator
     * @param SingleUnitModeService $singleUnitModeService
     * @param SystemDefaultProductUnitProvider $defaultProductUnitProvider
     */
    public function __construct(
        ProductUnitLabelFormatter $productUnitFormatter,
        TranslatorInterface $translator,
        SingleUnitModeService $singleUnitModeService,
        SystemDefaultProductUnitProvider $defaultProductUnitProvider
    ) {
        $this->singleUnitModeService = $singleUnitModeService;
        $this->defaultProductUnitProvider = $defaultProductUnitProvider;

        parent::__construct($productUnitFormatter, $translator);
    }

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

        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return $this->getProductUnitsForSingleUnitMode($primaryUnitPrecision);
        }

        /** @var ProductUnitPrecision[] $additionalUnitPrecisions */
        $additionalUnitPrecisions = $productForm->get('additionalUnitPrecisions')->getData();

        return $this->getProductUnitsForMultiUnitMode($primaryUnitPrecision, $additionalUnitPrecisions);
    }

    /**
     * @param ProductUnitPrecision $primaryUnitPrecision
     * @return ProductUnit[]
     */
    protected function getProductUnitsForSingleUnitMode(ProductUnitPrecision $primaryUnitPrecision)
    {
        $units = [];

        if ($primaryUnitPrecision) {
            $units[] = $primaryUnitPrecision->getUnit();
        }

        $defaultUnit = $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()->getUnit();

        if (($defaultUnit !== null) && ($defaultUnit->getCode() !== $primaryUnitPrecision->getUnit()->getCode())) {
            $units[] = $defaultUnit;
        }

        return $units;
    }

    /**
     * @param ProductUnitPrecision $primaryUnitPrecision
     * @param ProductUnitPrecision[] $additionalUnitPrecisions
     * @return ProductUnit[]
     */
    protected function getProductUnitsForMultiUnitMode(
        ProductUnitPrecision $primaryUnitPrecision,
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
