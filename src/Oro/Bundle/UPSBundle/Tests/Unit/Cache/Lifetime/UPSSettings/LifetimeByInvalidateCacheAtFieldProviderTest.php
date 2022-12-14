<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Cache\Lifetime\UPSSettings;

use Oro\Bundle\UPSBundle\Cache\Lifetime\UPSSettings\LifetimeByInvalidateCacheAtFieldProvider;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;

class LifetimeByInvalidateCacheAtFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PROCESSING_TIME_ERROR_VALUE = 3;
    private const LIFETIME = 86400;

    /** @var LifetimeByInvalidateCacheAtFieldProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new LifetimeByInvalidateCacheAtFieldProvider();
    }

    /**
     * @dataProvider savePriceDataProvider
     */
    public function testGetLifetime(string $invalidateCacheAtModifier, int $expectedLifetime)
    {
        $settings = $this->createMock(UPSSettings::class);

        $datetime = new \DateTime($invalidateCacheAtModifier);

        $settings->expects(self::any())
            ->method('getUpsInvalidateCacheAt')
            ->willReturn($datetime);

        $actualLifetime = $this->provider->getLifetime($settings, self::LIFETIME);

        self::assertLessThan(self::PROCESSING_TIME_ERROR_VALUE, abs($expectedLifetime - $actualLifetime));
    }

    public function savePriceDataProvider(): array
    {
        return [
            'earlier than lifetime' => [
                'invalidateCacheAt' => '+3second',
                'expectedLifetime' => 3,
            ],
            'in past' => [
                'invalidateCacheAt' => '-1second',
                'expectedLifetime' => self::LIFETIME,
            ],
            'later than lifetime' => [
                'invalidateCacheAt' => '+24hour+10second',
                'expectedLifetime' => self::LIFETIME,
            ],
        ];
    }

    public function testGenerateLifetimeAwareKey()
    {
        $settings = $this->createMock(UPSSettings::class);
        $settingId = 1;

        $datetime = $this->createMock(\DateTime::class);

        $datetime->expects(self::any())
            ->method('getTimestamp')
            ->willReturn(1000);

        $settings->expects(self::any())
            ->method('getUpsInvalidateCacheAt')
            ->willReturn($datetime);
        $settings->expects(self::any())
            ->method('getId')
            ->willReturn($settingId);

        $key = 'cache_key';

        $expectedKey = 'transport_' . $settingId . '_cache_key_1000';

        self::assertEquals($expectedKey, $this->provider->generateLifetimeAwareKey($settings, $key));
    }

    public function testGenerateLifetimeAwareKeyNull()
    {
        $settings = $this->createMock(UPSSettings::class);

        $key = 'cache_key';

        $expectedKey = 'transport__cache_key_';

        self::assertEquals($expectedKey, $this->provider->generateLifetimeAwareKey($settings, $key));
    }
}
