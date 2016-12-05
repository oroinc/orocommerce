<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitModeProvider
{
    /** @var SingleUnitModeService */
    private $singleUnitService;

    /**
     * @param SingleUnitModeService $singleUnitService
     */
    public function __construct(SingleUnitModeService $singleUnitService)
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
     * @param Product $product
     * @return bool
     */
    public function isProductPrimaryUnitSingleAndDefault(Product $product)
    {
        return $this->singleUnitService->isProductPrimaryUnitSingleAndDefault($product);
    }

    /**
     * @return string
     */
    public function getConfigDefaultUnit()
    {
        return $this->singleUnitService->getConfigDefaultUnit();
    }
}
