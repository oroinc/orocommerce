<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\PageEntityNameProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TranslationBundle\Entity\Language;

class PageEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private PageEntityNameProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new PageEntityNameProvider();
    }

    public function testGetNameForShortFormat(): void
    {
        self::assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new Page()));
        self::assertFalse($this->provider->getName(null, 'en', new Page()));
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForLocale(): void
    {
        $page = new Page();
        $page
            ->addTitle($this->getFallbackValue('default title'))
            ->addTitle($this->getFallbackValue('localized title', $this->getLocalization('en')));

        self::assertEquals(
            'default title',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $page)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $page = new Page();
        $page
            ->addTitle($this->getFallbackValue('default title'))
            ->addTitle($this->getFallbackValue('localized title', $this->getLocalization('en')));

        self::assertEquals(
            'localized title',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $page)
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Page::class, 'page')
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
