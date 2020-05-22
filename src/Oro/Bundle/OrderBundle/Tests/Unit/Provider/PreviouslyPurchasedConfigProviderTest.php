<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\PreviouslyPurchasedConfigProviderStub as PreviouslyPurchasedConfigProvider;
use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;

class PreviouslyPurchasedConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var PreviouslyPurchasedConfigProvider */
    protected $provider;

    /** @var  LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    protected $localeSettings;

    /** @var DateTimeFormatter|\PHPUnit\Framework\MockObject\MockObject  */
    protected $dateTimeFormatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->dateTimeFormatter = $this->createMock(DateTimeFormatter::class);

        $this->dateTimeFormatter
            ->method('format')
            ->willReturnCallback(
                function (\DateTime $dateTimeValue) {
                    return $dateTimeValue
                        ->setTimezone(new \DateTimeZone('UTC'))
                        ->format(DateTimeFormatter::DATETIME_FORMAT);
                }
            );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(
                Configuration::getConfigKey(Configuration::CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD),
                0
            )
            ->willReturn(1);

        $this->provider = new PreviouslyPurchasedConfigProvider(
            $this->configManager,
            $this->localeSettings,
            $this->dateTimeFormatter
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->provider, $this->configManager, $this->localeSettings, $this->dateTimeFormatter);
    }

    public function testGetDaysPeriod()
    {
        $this->assertEquals(1, $this->provider->getDaysPeriod());
    }

    public function testGetPreviouslyPurchasedStartDateWithUTCTimeZone()
    {
        $this->localeSettings
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

        $this->localeSettings
            ->method('getTimeZone')
            ->willReturn($timeZoneCode);

        $this->assertEquals(
            PreviouslyPurchasedConfigProvider::PREVIOUSLY_PURCHASED_DATE_STRING_WITH_BERLIN_LOCALE,
            $this->provider->getPreviouslyPurchasedStartDateString()
        );
    }
}
