<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

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

    /**
     * {@inheritdoc}
     */
    public function getDefaultProductUnitPrecision()
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            return $this->provider->getDefaultProductUnitPrecision();
        }
        return null;
    }
}
