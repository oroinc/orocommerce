<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\Provider;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Cached\Memory\CachedMemoryPaymentTermConfigProvider;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;

class CachedMemoryPaymentTermConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermConfigProvider;

    /** @var CachedMemoryPaymentTermConfigProvider */
    private $testedProvider;

    protected function setUp(): void
    {
        $this->paymentTermConfigProvider = $this->createMock(PaymentTermConfigProviderInterface::class);

        $this->testedProvider = new CachedMemoryPaymentTermConfigProvider($this->paymentTermConfigProvider);
    }

    public function testGetPaymentConfigs()
    {
        $expectedConfigs = [
            $this->createMock(PaymentTermConfigInterface::class),
            $this->createMock(PaymentTermConfigInterface::class)
        ];

        // if cache works this method is only called once
        $this->paymentTermConfigProvider->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn($expectedConfigs);

        $cachedConfigs = $this->testedProvider->getPaymentConfigs();

        $this->assertEquals($expectedConfigs, $cachedConfigs);

        $cachedConfigs = $this->testedProvider->getPaymentConfigs();

        $this->assertEquals($expectedConfigs, $cachedConfigs);
    }

    public function testHasPaymentConfig()
    {
        $expectedConfigs = [
            'someId' => $this->createMock(PaymentTermConfigInterface::class),
            'someOtherId' => $this->createMock(PaymentTermConfigInterface::class)
        ];

        // if cache works this method is only called once
        $this->paymentTermConfigProvider->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn($expectedConfigs);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertTrue($actualResult);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertTrue($actualResult);
    }

    public function testHasPaymentConfigWhenNoConfigs()
    {
        $this->paymentTermConfigProvider->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertFalse($actualResult);

        $actualResult = $this->testedProvider->hasPaymentConfig('someId');

        $this->assertFalse($actualResult);
    }

    public function testGetPaymentConfig()
    {
        $configOne = $this->createMock(PaymentTermConfigInterface::class);
        $expectedConfigs = [
            'someId' => $configOne,
            'someOtherId' => $this->createMock(PaymentTermConfigInterface::class)
        ];

        // if cache works this method is only called once
        $this->paymentTermConfigProvider->expects($this->once())
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
        $this->paymentTermConfigProvider->expects($this->once())
            ->method('getPaymentConfigs')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig('someId');

        $this->assertNull($actualResult);

        $actualResult = $this->testedProvider->getPaymentConfig('someId');

        $this->assertNull($actualResult);
    }
}
