<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentEntityNameProvider;
use Oro\Bundle\ConsentBundle\Tests\Unit\Stub\Consent;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TranslationBundle\Entity\Language;

class ConsentEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private ConsentEntityNameProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConsentEntityNameProvider();
    }

    public function testGetNameForShortFormat(): void
    {
        self::assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new Consent()));
        self::assertFalse($this->provider->getName(null, 'en', new Consent()));
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForLocale(): void
    {
        $consent = new Consent();
        $consent
            ->addName($this->getFallbackValue('default name'))
            ->addName($this->getFallbackValue('localized name', $this->getLocalization('en')));

        self::assertEquals(
            'default name',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $consent)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $consent = new Consent();
        $consent
            ->addName($this->getFallbackValue('default name'))
            ->addName($this->getFallbackValue('localized name', $this->getLocalization('en')));

        self::assertEquals(
            'localized name',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $consent)
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Consent::class, 'consent')
        );
    }

    private function getFallbackValue(string $string, Localization $localization = null): LocalizedFallbackValue
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)->setLocalization($localization);

        return $value;
    }

    private function getLocalization(string $code): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        $localization->setLanguage($language);

        return $localization;
    }
}
