<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

/**
 * Provides single unit mode information for layout rendering.
 *
 * This data provider exposes single unit mode settings to layout templates, allowing templates to adjust
 * their rendering based on whether the system is configured to use a single product unit or multiple units.
 */
class SingleUnitModeProvider
{
    /** @var SingleUnitModeService */
    private $singleUnitService;

    public function __construct(SingleUnitModeServiceInterface $singleUnitService)
    {
        $this->singleUnitService = $singleUnitService;
    }

    /**
     * @return bool
     */
    public function isSingleUnitMode()
    {
        return $this->singleUnitService->isSingleUnitMode();
    }

    /**
     * @return bool
     */
    public function isSingleUnitModeCodeVisible()
    {
        return $this->singleUnitService->isSingleUnitModeCodeVisible();
    }

    /**
     * @return ProductUnit|null
     */
    public function getDefaultUnitCode()
    {
        return $this->singleUnitService->getDefaultUnitCode();
    }
}
