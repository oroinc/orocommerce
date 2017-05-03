<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Layout\Provider;

use Oro\Bundle\ApruveBundle\Layout\Provider\ApruveJsUriProvider;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;

class ApruveJsUriProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveJsUriProvider
     */
    private $provider;

    /**
     * @var ApruveConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configProvider = $this->createMock(ApruveConfigProviderInterface::class);
        $this->config = $this->createMock(ApruveConfigInterface::class);

        $this->provider = new ApruveJsUriProvider($this->configProvider);
    }

    /**
     * @dataProvider getUriDataProvider
     *
     * @param string $paymentMethodIdentifier
     * @param bool $isTestMode
     * @param string $expectedUri
     */
    public function testGetUri($paymentMethodIdentifier, $isTestMode, $expectedUri)
    {
        $this->configProvider
            ->method('hasPaymentConfig')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->configProvider
            ->expects(static::once())
            ->method('getPaymentConfig')
            ->with($paymentMethodIdentifier)
            ->willReturn($this->config);

        $this->config
            ->expects(static::once())
            ->method('isTestMode')
            ->willReturn($isTestMode);

        $actual = $this->provider->getUri($paymentMethodIdentifier);

        static::assertSame($expectedUri, $actual);
    }

    /**
     * @return array
     */
    public function getUriDataProvider()
    {
        return [
            ['apruve_1', true, ApruveJsUriProvider::URI_TEST],
            ['apruve_2', false, ApruveJsUriProvider::URI_PROD],
        ];
    }

    public function testGetUriNull()
    {
        $paymentMethodIdentifier = 'apruve';

        $this->configProvider
            ->method('hasPaymentConfig')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->configProvider
            ->expects(static::never())
            ->method('getPaymentConfig');

        $actual = $this->provider->getUri($paymentMethodIdentifier);

        static::assertNull($actual);
    }
}
