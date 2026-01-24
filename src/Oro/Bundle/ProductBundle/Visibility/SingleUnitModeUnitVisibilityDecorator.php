<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

/**
 * Decorates unit visibility logic with single unit mode filtering.
 *
 * This decorator wraps another {@see UnitVisibilityInterface} implementation and applies single unit mode rules,
 * hiding unit codes when single unit mode is enabled and the unit code visibility setting is disabled.
 */
class SingleUnitModeUnitVisibilityDecorator implements UnitVisibilityInterface
{
    /**
     * @var UnitVisibilityInterface
     */
    private $unitVisibility;

    /**
     * @var SingleUnitModeServiceInterface
     */
    private $singleUnitModeService;

    public function __construct(
        UnitVisibilityInterface $unitVisibility,
        SingleUnitModeServiceInterface $singleUnitModeService
    ) {
        $this->unitVisibility = $unitVisibility;
        $this->singleUnitModeService = $singleUnitModeService;
    }

    /**
     * @param string $code
     * @return bool
     */
    #[\Override]
    public function isUnitCodeVisible($code)
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return $this->unitVisibility->isUnitCodeVisible($code);
        }
        return $this->singleUnitModeService->isSingleUnitModeCodeVisible()
            || $this->singleUnitModeService->getDefaultUnitCode() !== $code;
    }
}
