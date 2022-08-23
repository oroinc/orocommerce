<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider\FrontendLocalizationProvider;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FrontendLocalizationProviderTest extends TestCase
{
    protected LocalizationProviderInterface|MockObject $localizationProvider;

    protected UserLocalizationManagerInterface|MockObject $localizationManager;

    protected FrontendLocalizationProvider $dataProvider;

    protected function setUp(): void
    {
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->localizationManager = $this->createMock(UserLocalizationManagerInterface::class);

        $this->dataProvider = new FrontendLocalizationProvider(
            $this->localizationProvider,
            $this->localizationManager
        );
    }

    public function testGetEnabledLocalization()
    {
        $localizations = [new Localization(), new Localization()];

        $this->localizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);

        $this->assertSame($localizations, $this->dataProvider->getEnabledLocalizations());
    }

    public function testGetCurrentLocalization(): void
    {
        $localization = new Localization();

        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->dataProvider->getCurrentLocalization());
    }

    public function testGetCurrentLanguageCode(): void
    {
        $languageCode = 'de_DE';
        $expectedLanguageCode = 'de-DE';

        $localization = $this->getLocalizationWithLanguage($languageCode);

        $this->localizationProvider
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($expectedLanguageCode, $this->dataProvider->getCurrentLanguageCode());
    }

    public function testGetCurrentLanguageCodeWhenNoCurrent(): void
    {
        $languageCode = 'en_US';
        $expectedLanguageCode = 'en-US';

        $localization = $this->getLocalizationWithLanguage($languageCode);

        $this->localizationProvider
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->localizationManager
            ->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $this->assertSame($expectedLanguageCode, $this->dataProvider->getCurrentLanguageCode());
    }

    private function getLocalizationWithLanguage(string $languageCode): Localization
    {
        $language = new Language();
        $language->setCode($languageCode);

        $localization = new Localization();
        $localization->setLanguage($language);

        return $localization;
    }
}
