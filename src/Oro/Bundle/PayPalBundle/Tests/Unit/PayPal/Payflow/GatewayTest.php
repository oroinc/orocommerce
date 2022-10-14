<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\ClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorRegistry;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestRegistry;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;

class GatewayTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var RequestRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $requestRegistry;

    /** @var ClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var Gateway */
    private $gateway;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->requestRegistry = $this->createMock(RequestRegistry::class);
        $this->client = $this->createMock(ClientInterface::class);

        $hostAddressProvider = $this->createMock(HostAddressProviderInterface::class);
        $hostAddressProvider->expects($this->any())
            ->method('getHostAddress')
            ->willReturnMap([
                [true, HostAddressProvider::PILOT_HOST_ADDRESS],
                [false, HostAddressProvider::PRODUCTION_HOST_ADDRESS]
            ]);
        $hostAddressProvider->expects($this->any())
            ->method('getFormAction')
            ->willReturnMap([
                [true, HostAddressProvider::PILOT_FORM_ACTION],
                [false, HostAddressProvider::PRODUCTION_FORM_ACTION]
            ]);

        $this->gateway = new Gateway(
            $hostAddressProvider,
            $this->client,
            $this->requestRegistry,
            $this->processorRegistry
        );
    }

    public function testRequest()
    {
        $action = 'ACTION';
        $options = [
            Partner::PARTNER => 'PARTNER',
        ];

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('configureOptions')
            ->with($this->isInstanceOf(OptionsResolver::class))
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->setDefined(Partner::PARTNER);
            });

        $this->requestRegistry->expects($this->once())
            ->method('getRequest')
            ->with($action)
            ->willReturn($request);

        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())
            ->method('configureOptions')
            ->with($this->isInstanceOf(OptionsResolver::class));

        $this->processorRegistry->expects($this->once())
            ->method('getProcessor')
            ->with($options[Partner::PARTNER])
            ->willReturn($processor);

        $responseData = ['response' => 'data'];
        $this->client->expects($this->once())
            ->method('send')
            ->with(HostAddressProvider::PILOT_HOST_ADDRESS)
            ->willReturn($responseData);

        $this->gateway->setTestMode(true);
        $response = $this->gateway->request($action, $options);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($responseData, $response->getData());
    }

    public function testGetFormAction()
    {
        $this->gateway->setTestMode(true);
        $this->assertEquals(HostAddressProvider::PILOT_FORM_ACTION, $this->gateway->getFormAction());

        $this->gateway->setTestMode(false);
        $this->assertEquals(HostAddressProvider::PRODUCTION_FORM_ACTION, $this->gateway->getFormAction());
    }

    public function sslVerificationEnabledProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider sslVerificationEnabledProvider
     */
    public function testSslVerificationEnabledIsPassedToClientInRequest(bool $enabled)
    {
        $this->prepareRequest();

        $this->client->expects($this->once())
            ->method('send')
            ->with($this->isType('string'), $this->isType('array'), ['SSL_VERIFY' => $enabled])
            ->willReturn([]);

        $this->gateway->setSslVerificationEnabled($enabled);
        $this->gateway->request('ACTION', [
            Partner::PARTNER => 'PARTNER',
        ]);
    }

    public function testProxyAddressOptionIsNotPassedToClientIfProxyAddressWasNotSet()
    {
        $this->prepareRequest();

        $this->client->expects($this->once())
            ->method('send')
            ->with($this->isType('string'), $this->isType('array'), ['SSL_VERIFY' => true])
            ->willReturn([]);

        $this->gateway->request('ACTION', [
            Partner::PARTNER => 'PARTNER',
        ]);
    }

    public function testProxyAddressOptionIsPassedToClientIfProxyAddressWasSet()
    {
        $this->prepareRequest();

        $proxyHost = '12.23.34.45';
        $proxyPort = 5555;

        $this->client->expects($this->once())
            ->method('send')
            ->with(
                $this->isType('string'),
                $this->isType('array'),
                [
                    'SSL_VERIFY' => true,
                    'PROXY_HOST' => $proxyHost,
                    'PROXY_PORT' => $proxyPort,
                ]
            )
            ->willReturn([]);

        $this->gateway->setProxySettings($proxyHost, $proxyPort);

        $this->gateway->request('ACTION', [
            Partner::PARTNER => 'PARTNER',
        ]);
    }

    private function prepareRequest()
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('configureOptions')
            ->with($this->isInstanceOf(OptionsResolver::class))
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->setDefined(Partner::PARTNER);
            });

        $this->requestRegistry->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $processor = $this->createMock(ProcessorInterface::class);
        $this->processorRegistry->expects($this->once())
            ->method('getProcessor')
            ->willReturn($processor);
    }
}
