<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitCategoryDefaultProductOptionsVisibilityDecorator implements
    CategoryDefaultProductOptionsVisibilityInterface
{
    /**
     * @var CategoryDefaultProductOptionsVisibilityInterface
     */
    private $optionsVisibility;

    /**
     * @var SingleUnitModeService
     */
    private $singleUnitModeService;

    /**
     * @param CategoryDefaultProductOptionsVisibilityInterface $optionsVisibility
     * @param SingleUnitModeService $singleUnitModeService
     */
    public function __construct(
        CategoryDefaultProductOptionsVisibilityInterface $optionsVisibility,
        SingleUnitModeService $singleUnitModeService
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
