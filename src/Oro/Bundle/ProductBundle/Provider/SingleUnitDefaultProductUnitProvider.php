<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

/**
 * Provides default product unit precision when single unit mode is enabled.
 *
 * This provider decorates another {@see DefaultProductUnitProviderInterface} implementation,
 * returning the default unit only when single unit mode is active, otherwise returning null
 * to allow multiple units to be used.
 */
class SingleUnitDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
{
    /**
     * @var SingleUnitModeServiceInterface
     */
    private $singleUnitModeService;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    private $provider;

    public function __construct(
        SingleUnitModeServiceInterface $singleUnitModeService,
        DefaultProductUnitProviderInterface $provider
    ) {
        $this->singleUnitModeService = $singleUnitModeService;
        $this->provider = $provider;
    }

    #[\Override]
    public function getDefaultProductUnitPrecision()
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return $this->provider->getDefaultProductUnitPrecision();
        }
        return null;
    }
}
