<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\ClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorRegistry;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestRegistry;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;

class Gateway
{
    /** @var ClientInterface */
    protected $client;

    /** @var RequestRegistry */
    protected $requestRegistry;

    /** @var ProcessorRegistry */
    protected $processorRegistry;

    /** @var bool */
    protected $testMode = false;

    /** @var bool */
    protected $sslVerificationEnabled = true;

    /** @var string */
    protected $proxyHost;

    /** @var int */
    protected $proxyPort;

    /** @var HostAddressProviderInterface */
    protected $hostAddressProvider;

    public function __construct(
        HostAddressProviderInterface $hostAddressProvider,
        ClientInterface $client,
        RequestRegistry $requestRegistry,
        ProcessorRegistry $processorRegistry
    ) {
        $this->hostAddressProvider = $hostAddressProvider;
        $this->client = $client;
        $this->requestRegistry = $requestRegistry;
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @param string $transactionType
     * @param array $options
     * @return ResponseInterface
     */
    public function request($transactionType, array $options = [])
    {
        $resolver = new OptionsResolver();
        $request = $this->requestRegistry->getRequest($transactionType);
        $request->configureOptions($resolver);

        $processor = $this->processorRegistry->getProcessor($options[Partner::PARTNER]);
        $processor->configureOptions($resolver);

        $connectionOptions = [
            ClientInterface::SSL_VERIFY => $this->sslVerificationEnabled,
        ];

        if ($this->proxyHost && $this->proxyPort) {
            $connectionOptions[ClientInterface::PROXY_HOST] = $this->proxyHost;
            $connectionOptions[ClientInterface::PROXY_PORT] = $this->proxyPort;
        }

        $responseData = $this->client->send(
            $this->getHostAddress(),
            $resolver->resolve($options),
            $connectionOptions
        );

        return new Response($responseData);
    }

    /**
     * @return string
     */
    public function getHostAddress()
    {
        return $this->hostAddressProvider->getHostAddress($this->testMode);
    }

    /**
     * @return string
     */
    public function getFormAction()
    {
        return $this->hostAddressProvider->getFormAction($this->testMode);
    }

    /**
     * @param bool $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = (bool)$testMode;
    }

    /**
     * @param bool $enabled
     */
    public function setSslVerificationEnabled($enabled)
    {
        $this->sslVerificationEnabled = $enabled;
    }

    /**
     * @param string $proxyHost
     * @param int $proxyPort
     */
    public function setProxySettings($proxyHost, $proxyPort)
    {
        $this->proxyHost = $proxyHost;
        $this->proxyPort = $proxyPort;
    }
}
