<?php

namespace Oro\Bundle\ProductBundle\Service;

interface SingleUnitModeServiceInterface
{
    /**
     * @return bool
     */
    public function isSingleUnitMode();

    /**
     * @return bool
     */
    public function isSingleUnitModeCodeVisible();

    /**
     * @return string
     */
    public function getDefaultUnitCode();
}
