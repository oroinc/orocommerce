<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

/**
 * Decorator that controls visibility of category default product unit options based on single unit mode.
 *
 * Wraps another visibility implementation and disables default unit precision selection when
 * the system is in single unit mode, ensuring consistent behavior across the application.
 */
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

    #[\Override]
    public function isDefaultUnitPrecisionSelectionAvailable()
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return false;
        }
        return $this->optionsVisibility->isDefaultUnitPrecisionSelectionAvailable();
    }
}
