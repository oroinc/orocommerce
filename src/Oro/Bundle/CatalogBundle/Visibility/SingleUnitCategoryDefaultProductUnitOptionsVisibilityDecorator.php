<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

class SingleUnitCategoryDefaultProductUnitOptionsVisibilityDecorator implements
    CategoryDefaultProductUnitOptionsVisibilityInterface
{
    /**
     * @var CategoryDefaultProductUnitOptionsVisibilityInterface
     */
    private $optionsVisibility;

    /**
     * @var SingleUnitModeServiceInterface
     */
    private $singleUnitModeService;

    public function __construct(
        CategoryDefaultProductUnitOptionsVisibilityInterface $optionsVisibility,
        SingleUnitModeServiceInterface $singleUnitModeService
    ) {
        $this->optionsVisibility = $optionsVisibility;
        $this->singleUnitModeService = $singleUnitModeService;
    }

    /**
     * {@inheritdoc}
     */
    public function isDefaultUnitPrecisionSelectionAvailable()
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return false;
        }
        return $this->optionsVisibility->isDefaultUnitPrecisionSelectionAvailable();
    }
}
