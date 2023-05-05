<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\PreviouslyPurchasedConfigProviderStub as PreviouslyPurchasedConfigProvider;
use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;

class PreviouslyPurchasedConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var PreviouslyPurchasedConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD), 0)
            ->willReturn(1);

        $dateTimeFormatter = $this->createMock(DateTimeFormatter::class);
        $dateTimeFormatter->expects(self::any())
            ->method('format')
            ->willReturnCallback(function (\DateTime $dateTimeValue) {
                return $dateTimeValue
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(DateTimeFormatter::DATETIME_FORMAT);
            });

        $this->provider = new PreviouslyPurchasedConfigProvider(
            $configManager,
            $this->localeSettings,
            $dateTimeFormatter
        );
    }

    public function testGetDaysPeriod()
    {
        $this->assertEquals(1, $this->provider->getDaysPeriod());
    }

    public function testGetPreviouslyPurchasedStartDateWithUTCTimeZone()
    {
        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $this->assertEquals(
            PreviouslyPurchasedConfigProvider::PREVIOUSLY_PURCHASED_DATE_STRING_WITH_UTC_LOCALE,
            $this->provider->getPreviouslyPurchasedStartDateString()
        );
    }

    public function testGetPreviouslyPurchasedStartDateWithBerlinTimeZone()
    {
        $timeZoneCode = 'Europe/Berlin';

        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn($timeZoneCode);

        $this->assertEquals(
            PreviouslyPurchasedConfigProvider::PREVIOUSLY_PURCHASED_DATE_STRING_WITH_BERLIN_LOCALE,
            $this->provider->getPreviouslyPurchasedStartDateString()
        );
    }
}
