<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;
use Oro\Bundle\SoapBundle\Client\SoapClientInterface;

class FedexRateServiceSoapClient implements FedexRateServiceClientInterface
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
     * @param SoapClientInterface                      $soapClient
     * @param FedexRateServiceResponseFactoryInterface $responseFactory
     * @param SoapClientSettingsInterface              $soapSettings
     */
    public function __construct(
        SoapClientInterface $soapClient,
        FedexRateServiceResponseFactoryInterface $responseFactory,
        SoapClientSettingsInterface $soapSettings
    ) {
        $this->soapClient = $soapClient;
        $this->responseFactory = $responseFactory;
        $this->soapSettings = $soapSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function send(FedexRequestInterface $request): FedexRateServiceResponseInterface
    {
        $soapResponse = $this->soapClient->send($this->soapSettings, $request->getRequestData());

        return $this->responseFactory->create($soapResponse);
    }
}
