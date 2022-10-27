<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

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
    public function isUnitCodeVisible($code)
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return $this->unitVisibility->isUnitCodeVisible($code);
        }
        return $this->singleUnitModeService->isSingleUnitModeCodeVisible()
            || $this->singleUnitModeService->getDefaultUnitCode() !== $code;
    }
}
