<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;
use Oro\Bundle\SoapBundle\Client\SoapClientInterface;

class FedexRateServiceSoapClient implements FedexRateServiceBySettingsClientInterface
{
    /**
     * @var SoapClientInterface
     */
    private $soapClient;

    /**
     * @var FedexRateServiceResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var SoapClientSettingsInterface
     */
    private $soapSettings;

    /**
     * @var SoapClientSettingsInterface
     */
    private $soapTestSettings;

    public function __construct(
        SoapClientInterface $soapClient,
        FedexRateServiceResponseFactoryInterface $responseFactory,
        SoapClientSettingsInterface $soapSettings,
        SoapClientSettingsInterface $soapTestSettings
    ) {
        $this->soapClient = $soapClient;
        $this->responseFactory = $responseFactory;
        $this->soapSettings = $soapSettings;
        $this->soapTestSettings = $soapTestSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function send(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexRateServiceResponseInterface {
        try {
            $soapResponse = $this->soapClient->send(
                $this->getSoapSettings($settings),
                $request->getRequestData()
            );

            return $this->responseFactory->create($soapResponse);
        } catch (\Exception $e) {
            return $this->responseFactory->create(null);
        }
    }

    private function getSoapSettings(FedexIntegrationSettings $settings): SoapClientSettingsInterface
    {
        if ($settings->isFedexTestMode()) {
            return $this->soapTestSettings;
        }

        return $this->soapSettings;
    }
}
