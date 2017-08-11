<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\SoapBundle\Client\Settings\Factory\SoapClientSettingsFactoryInterface;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;
use Oro\Bundle\SoapBundle\Client\SoapClientInterface;

class FedexRateServiceSoapClient implements FedexRateServiceClientInterface
{
    /**
     * @internal
     */
    const METHOD_NAME = 'getRates';

    /**
     * @internal in seconds
     */
    const TIMEOUT = 3;

    /**
     * @var SoapClientInterface
     */
    private $soapClient;

    /**
     * @var SoapClientSettingsFactoryInterface
     */
    private $soapSettingsFactory;

    /**
     * @var FedexRateServiceResponseInterface
     */
    private $responseFactory;

    /**
     * @param SoapClientInterface                      $soapClient
     * @param SoapClientSettingsFactoryInterface       $soapSettingsFactory
     * @param FedexRateServiceResponseFactoryInterface $responseFactory
     */
    public function __construct(
        SoapClientInterface $soapClient,
        SoapClientSettingsFactoryInterface $soapSettingsFactory,
        FedexRateServiceResponseFactoryInterface $responseFactory
    ) {
        $this->soapClient = $soapClient;
        $this->soapSettingsFactory = $soapSettingsFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function send(FedexRequestInterface $request): FedexRateServiceResponseInterface
    {
        $soapResponse = $this->soapClient->send($this->createSettings(), $request->getRequestData());

        return $this->responseFactory->create($soapResponse);
    }

    /**
     * @return SoapClientSettingsInterface
     */
    private function createSettings(): SoapClientSettingsInterface
    {
        return $this->soapSettingsFactory->create(
            $this->getWsdlFilePath(),
            self::METHOD_NAME,
            [
                SoapClientSettings::OPTION_TIMEOUT => self::TIMEOUT,
            ]
        );
    }

    /**
     * @return string
     */
    private function getWsdlFilePath(): string
    {
        return __DIR__ . '/wsdl/RateService_v20.wsdl';
    }
}
