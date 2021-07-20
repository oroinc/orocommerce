<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

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
