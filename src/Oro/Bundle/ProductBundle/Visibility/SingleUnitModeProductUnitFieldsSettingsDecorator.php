<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Exception\DefaultUnitNotFoundException;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

/**
 * This service decorates basic functionality when product single unit mode is enabled.
 */
class SingleUnitModeProductUnitFieldsSettingsDecorator implements ProductUnitFieldsSettingsInterface
{
    public function __construct(
        private ProductUnitFieldsSettingsInterface $settings,
        private SingleUnitModeServiceInterface $singleUnitModeService,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function isProductUnitSelectionVisible(?Product $product): bool
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return !$this->isProductPrimaryUnitSingleAndDefault($product);
        }
        return $this->settings->isProductUnitSelectionVisible($product);
    }

    #[\Override]
    public function isProductPrimaryUnitVisible(?Product $product = null): bool
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return $product && !$this->isProductPrimaryUnitSingleAndDefault($product);
        }
        return $this->settings->isProductPrimaryUnitVisible($product);
    }

    #[\Override]
    public function isAddingAdditionalUnitsToProductAvailable(?Product $product = null): bool
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return false;
        }
        return $this->settings->isAddingAdditionalUnitsToProductAvailable($product);
    }

    /**
     * @throws DefaultUnitNotFoundException
     */
    #[\Override]
    public function getAvailablePrimaryUnitChoices(?Product $product = null): array
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return $this->settings->getAvailablePrimaryUnitChoices($product);
        }
        $units = [];
        $defaultUnitCode = $this->singleUnitModeService->getDefaultUnitCode();
        $defaultUnit = $this->doctrineHelper->getEntityRepository(ProductUnit::class)->find($defaultUnitCode);
        if (!$defaultUnit) {
            throw new DefaultUnitNotFoundException('There is no default product unit found in the system');
        }

        $units[] = $defaultUnit;
        if (!$product) {
            return $units;
        }
        $primaryUnitPrecision = $product->getPrimaryUnitPrecision();
        if ($primaryUnitPrecision) {
            $primaryUnitCode = $primaryUnitPrecision->getUnit()->getCode();
            if ($defaultUnitCode !== $primaryUnitCode) {
                $units[] = $primaryUnitPrecision->getUnit();
            }
        }

        return $units;
    }

    private function isProductPrimaryUnitSingleAndDefault(Product $product): bool
    {
        $defaultUnitCode = $this->singleUnitModeService->getDefaultUnitCode();

        return $defaultUnitCode === $product->getPrimaryUnitPrecision()->getUnit()->getCode()
            && $product->getAdditionalUnitPrecisions()
                ->filter(function (ProductUnitPrecision $precision) {
                    return $precision->isSell();
                })->isEmpty();
    }
}
