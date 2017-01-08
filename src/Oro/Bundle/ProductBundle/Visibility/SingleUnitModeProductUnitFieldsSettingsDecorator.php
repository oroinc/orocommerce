<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

class SingleUnitModeProductUnitFieldsSettingsDecorator implements ProductUnitFieldsSettingsInterface
{
    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    private $settings;

    /**
     * @var SingleUnitModeServiceInterface
     */
    private $singleUnitModeService;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param ProductUnitFieldsSettingsInterface $settings
     * @param SingleUnitModeServiceInterface $singleUnitModeService
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ProductUnitFieldsSettingsInterface $settings,
        SingleUnitModeServiceInterface $singleUnitModeService,
        DoctrineHelper $doctrineHelper
    ) {
        $this->settings = $settings;
        $this->singleUnitModeService = $singleUnitModeService;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isProductUnitSelectionVisible(Product $product)
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return !$this->isProductPrimaryUnitSingleAndDefault($product);
        }
        return $this->settings->isProductUnitSelectionVisible($product);
    }

    /**
     * {@inheritdoc}
     */
    public function isProductPrimaryUnitVisible(Product $product = null)
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return $product && !$this->isProductPrimaryUnitSingleAndDefault($product);
        }
        return $this->settings->isProductPrimaryUnitVisible($product);
    }

    /**
     * {@inheritdoc}
     */
    public function isAddingAdditionalUnitsToProductAvailable(Product $product = null)
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return false;
        }
        return $this->settings->isAddingAdditionalUnitsToProductAvailable($product);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePrimaryUnitChoices(Product $product = null)
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return $this->settings->getAvailablePrimaryUnitChoices($product);
        }
        $units = [];
        if (!$product) {
            return $units;
        }
        $primaryUnitPrecision = $product->getPrimaryUnitPrecision();
        if ($primaryUnitPrecision) {
            $units[] = $primaryUnitPrecision->getUnit();
            $primaryUnitCode = $primaryUnitPrecision->getUnit()->getCode();
            $defaultUnitCode = $this->singleUnitModeService->getDefaultUnitCode();
            $defaultUnit = $this->doctrineHelper->getEntityReference(ProductUnit::class, $defaultUnitCode);
            if ($defaultUnit && $defaultUnitCode !== $primaryUnitCode) {
                $units[] = $defaultUnit;
            }
        }
        return $units;
    }

    /**
     * @param Product $product
     * @return bool
     */
    private function isProductPrimaryUnitSingleAndDefault(Product $product)
    {
        $defaultUnitCode = $this->singleUnitModeService->getDefaultUnitCode();
        return $defaultUnitCode === $product->getPrimaryUnitPrecision()->getUnit()->getCode()
            && $product->getAdditionalUnitPrecisions()->isEmpty();
    }
}
