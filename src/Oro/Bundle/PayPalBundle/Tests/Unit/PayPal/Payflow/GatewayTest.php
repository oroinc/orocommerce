<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\ClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Host\HostAddressProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Partner;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorRegistry;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestRegistry;

class GatewayTest extends \PHPUnit\Framework\TestCase
{
    /** @var Gateway */
    protected $gateway;

    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $processorRegistry;

    /** @var RequestRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestRegistry;

    /** @var ClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $client;

    /** @var HostAddressProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $hostAddressProvider;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(
            'Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorRegistry'
        );

        $this->requestRegistry = $this
            ->getMockBuilder('Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->client = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Client\ClientInterface');

        $this->hostAddressProvider = $this->createMock(HostAddressProviderInterface::class);
        $this->hostAddressProvider->expects($this->any())
            ->method('getHostAddress')
            ->will($this->returnValueMap([
                [true, HostAddressProvider::PILOT_HOST_ADDRESS],
                [false, HostAddressProvider::PRODUCTION_HOST_ADDRESS]
            ]));
        $this->hostAddressProvider->expects($this->any())
            ->method('getFormAction')
            ->will($this->returnValueMap([
                [true, HostAddressProvider::PILOT_FORM_ACTION],
                [false, HostAddressProvider::PRODUCTION_FORM_ACTION]
            ]));

        $this->gateway = new Gateway(
            $this->hostAddressProvider,
            $this->client,
            $this->requestRegistry,
            $this->processorRegistry
        );
    }

    protected function tearDown(): void
    {
        unset($this->gateway, $this->client, $this->requestRegistry, $this->processorRegistry);
    }

    public function testRequest()
    {
        $action = 'ACTION';
        $options = [
            Partner::PARTNER => 'PARTNER',
        ];

        $request = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface');
        $request
            ->expects($this->once())
            ->method('configureOptions')
            ->with(
                $this->isInstanceOf('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver')
            )
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->setDefined(Partner::PARTNER);
            });

        $this->requestRegistry
            ->expects($this->once())
            ->method('getRequest')
            ->with($action)
            ->willReturn($request);

        $processor = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorInterface');
        $processor
            ->expects($this->once())
            ->method('configureOptions')
            ->with(
                $this->isInstanceOf('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver')
            );

        $this->processorRegistry
            ->expects($this->once())
            ->method('getProcessor')
            ->with($options[Partner::PARTNER])
            ->willReturn($processor);

        $responseData = ['response' => 'data'];
        $this->client
            ->expects($this->once())
            ->method('send')
            ->with(HostAddressProvider::PILOT_HOST_ADDRESS)
            ->willReturn($responseData);

        $this->gateway->setTestMode(true);
        $response = $this->gateway->request($action, $options);

        $this->assertInstanceOf('Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface', $response);
        $this->assertEquals($responseData, $response->getData());
    }

    public function testGetFormAction()
    {
        $this->gateway->setTestMode(true);
        $this->assertEquals(HostAddressProvider::PILOT_FORM_ACTION, $this->gateway->getFormAction());

        $this->gateway->setTestMode(false);
        $this->assertEquals(HostAddressProvider::PRODUCTION_FORM_ACTION, $this->gateway->getFormAction());
    }

    /**
     * @return array
     */
    public function sslVerificationEnabledProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider sslVerificationEnabledProvider
     * @param bool $enabled
     */
    public function testSslVerificationEnabledIsPassedToClientInRequest($enabled)
    {
        $this->prepareRequest();

        $this->client
            ->expects($this->once())
            ->method('send')
            ->with(
                $this->isType('string'),
                $this->isType('array'),
                ['SSL_VERIFY' => $enabled]
            )
            ->willReturn([]);

        $this->gateway->setSslVerificationEnabled($enabled);
        $this->gateway->request('ACTION', [
            Partner::PARTNER => 'PARTNER',
        ]);
    }

    public function testProxyAddressOptionIsNotPassedToClientIfProxyAddressWasNotSet()
    {
        $this->prepareRequest();

        $this->client
            ->expects($this->once())
            ->method('send')
            ->with(
                $this->isType('string'),
                $this->isType('array'),
                ['SSL_VERIFY' => true]
            )
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

        $this->client
            ->expects($this->once())
            ->method('send')
            ->with(
                $this->isType('string'),
                $this->isType('array'),
                [
                    'SSL_VERIFY'    => true,
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

    protected function prepareRequest()
    {
        $request = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface');
        $request
            ->expects($this->once())
            ->method('configureOptions')
            ->with(
                $this->isInstanceOf('Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver')
            )
            ->willReturnCallback(function (OptionsResolver $resolver) {
                $resolver->setDefined(Partner::PARTNER);
            });

        $this->requestRegistry
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $processor = $this->createMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor\ProcessorInterface');
        $this->processorRegistry
            ->expects($this->once())
            ->method('getProcessor')
            ->willReturn($processor);
    }
}
