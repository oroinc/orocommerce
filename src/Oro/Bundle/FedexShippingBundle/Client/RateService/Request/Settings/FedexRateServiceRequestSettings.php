<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class FedexRateServiceRequestSettings implements FedexRateServiceRequestSettingsInterface
{
    /**
     * @var FedexIntegrationSettings
     */
    private $integrationSettings;

    /**
     * @var ShippingContextInterface
     */
    private $shippingContext;

    /**
     * @var ShippingService
     */
    private $service;

    /**
     * @param FedexIntegrationSettings $integrationSettings
     * @param ShippingContextInterface $shippingContext
     * @param ShippingService          $service
     */
    public function __construct(
        FedexIntegrationSettings $integrationSettings,
        ShippingContextInterface $shippingContext,
        ShippingService $service
    ) {
        $this->integrationSettings = $integrationSettings;
        $this->shippingContext = $shippingContext;
        $this->service = $service;
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegrationSettings(): FedexIntegrationSettings
    {
        return $this->integrationSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingContext(): ShippingContextInterface
    {
        return $this->shippingContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingService(): ShippingService
    {
        return $this->service;
    }
}
