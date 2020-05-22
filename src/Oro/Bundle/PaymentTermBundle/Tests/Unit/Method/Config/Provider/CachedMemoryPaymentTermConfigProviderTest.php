<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\Provider;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Cached\Memory\CachedMemoryPaymentTermConfigProvider;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;

class CachedMemoryPaymentTermConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CachedMemoryPaymentTermConfigProvider
     */
    private $testedProvider;

    /**
     * @var PaymentTermConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentTermConfigProviderMock;

    protected function setUp(): void
    {
        $this->paymentTermConfigProviderMock = $this->createMock(PaymentTermConfigProviderInterface::class);
        $this->testedProvider = new CachedMemoryPaymentTermConfigProvider($this->paymentTermConfigProviderMock);
    }

    /**
     * @return PaymentTermConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createConfigMock()
    {
        return $this->createMock(PaymentTermConfigInterface::class);
    }

    public function testGetPaymentConfigs()
    {
        $expectedConfigs = [$this->createConfigMock(), $this->createConfigMock()];

        // if cache works this method is only called once
        $this->paymentTermConfigProviderMock
            ->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn($expectedConfigs);

        $cachedConfigs = $this->testedProvider->getPaymentConfigs();

        $this->assertEquals($expectedConfigs, $cachedConfigs);

        $cachedConfigs = $this->testedProvider->getPaymentConfigs();

        $this->assertEquals($expectedConfigs, $cachedConfigs);
    }

    public function testHasPaymentConfig()
    {
        $expectedConfigs = ['someId' => $this->createConfigMock(), 'someOtherId' => $this->createConfigMock()];

        // if cache works this method is only called once
        $this->paymentTermConfigProviderMock
            ->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn($expectedConfigs);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertTrue($actualResult);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertTrue($actualResult);
    }

    public function testHasPaymentConfigWhenNoConfigs()
    {
        $this->paymentTermConfigProviderMock
            ->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertFalse($actualResult);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertFalse($actualResult);
    }

    public function testGetPaymentConfig()
    {
        $configOne = $this->createConfigMock();
        $expectedConfigs = ['someId' => $configOne, 'someOtherId' => $this->createConfigMock()];

        // if cache works this method is only called once
        $this->paymentTermConfigProviderMock
            ->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn($expectedConfigs);

        $actualResult = $this->testedProvider->getPaymentConfig('someId');

        $this->assertEquals($configOne, $actualResult);

        $actualResult = $this->testedProvider->getPaymentConfig('someId');

        $this->assertEquals($configOne, $actualResult);
    }

    public function testGetPaymentConfigWhenNoConfigs()
    {
        // if cache works this method is only called once
        $this->paymentTermConfigProviderMock
            ->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig('someId');

        $this->assertNull($actualResult);

        $actualResult = $this->testedProvider->getPaymentConfig('someId');

        $this->assertNull($actualResult);
    }
}
