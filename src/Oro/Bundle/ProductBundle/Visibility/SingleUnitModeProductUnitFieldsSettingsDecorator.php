<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Exception\DefaultUnitNotFoundException;
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
     * @throws DefaultUnitNotFoundException
     */
    public function getAvailablePrimaryUnitChoices(Product $product = null)
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

    /**
     * @param Product $product
     * @return bool
     */
    private function isProductPrimaryUnitSingleAndDefault(Product $product)
    {
        $defaultUnitCode = $this->singleUnitModeService->getDefaultUnitCode();

        return $defaultUnitCode === $product->getPrimaryUnitPrecision()->getUnit()->getCode()
            && $product->getAdditionalUnitPrecisions()
                ->filter(function (ProductUnitPrecision $precision) {
                    return $precision->isSell();
                })->isEmpty();
    }
}
