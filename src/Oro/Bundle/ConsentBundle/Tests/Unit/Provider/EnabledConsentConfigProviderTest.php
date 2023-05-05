<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentConfigProvider;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class EnabledConsentConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConsentConfigConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $converter;

    /** @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contextProvider;

    /** @var EnabledConsentConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->converter = $this->createMock(ConsentConfigConverter::class);
        $this->contextProvider = $this->createMock(ConsentContextProvider::class);

        $this->provider = new EnabledConsentConfigProvider(
            $this->configManager,
            $this->converter,
            $this->contextProvider
        );
    }

    public function testGetConsentConfigs(): void
    {
        $website = $this->createMock(Website::class);
        $consentConfigs = [[ConsentConfigConverter::CONSENT_KEY => 1]];
        $convertedConsentConfigs = [$this->createMock(ConsentConfig::class)];

        $this->contextProvider->expects(self::once())
            ->method('getWebsite')
            ->willReturn($website);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_consent.enabled_consents', false, false, self::identicalTo($website))
            ->willReturn($consentConfigs);

        $this->converter->expects(self::once())
            ->method('convertFromSaved')
            ->with($consentConfigs)
            ->willReturn($convertedConsentConfigs);

        self::assertEquals($convertedConsentConfigs, $this->provider->getConsentConfigs());
    }

    public function testGetConsentConfigsWhenWebsiteNotResolved(): void
    {
        $this->contextProvider->expects(self::once())
            ->method('getWebsite')
            ->willReturn(null);

        $this->configManager->expects(self::never())
            ->method('get');

        $this->converter->expects(self::never())
            ->method('convertFromSaved');

        self::assertSame([], $this->provider->getConsentConfigs());
    }

    public function testGetConsentConfigsWhenConfigValueIsNull(): void
    {
        $website = $this->createMock(Website::class);

        $this->contextProvider->expects(self::once())
            ->method('getWebsite')
            ->willReturn($website);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_consent.enabled_consents', false, false, self::identicalTo($website))
            ->willReturn(null);

        $this->converter->expects(self::once())
            ->method('convertFromSaved')
            ->with([])
            ->willReturn([]);

        self::assertSame([], $this->provider->getConsentConfigs());
    }
}
