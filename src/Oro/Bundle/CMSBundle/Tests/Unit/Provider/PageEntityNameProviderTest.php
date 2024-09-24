<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\PageEntityNameProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;

class PageEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private PageEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new PageEntityNameProvider();
    }

    private function getPageTitle(string $string, Localization $localization = null): LocalizedFallbackValue
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string);
        $value->setLocalization($localization);

        return $value;
    }

    private function getLocalization(string $code): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        ReflectionUtil::setId($localization, 123);
        $localization->setLanguage($language);

        return $localization;
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $page = new Page();
        $page->addTitle($this->getPageTitle('default title'));
        $page->addTitle($this->getPageTitle('localized title', $this->getLocalization('en')));

        self::assertEquals(
            'default title',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $page)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $page = new Page();
        $page->addTitle($this->getPageTitle('default title'));
        $page->addTitle($this->getPageTitle('localized title', $this->getLocalization('en')));

        self::assertEquals(
            'localized title',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $page)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, \stdClass::class, 'page')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(page_t.string, page_t.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue page_t'
            . ' WHERE page_t MEMBER OF page.titles AND page_t.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, Page::class, 'page')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(page_t.string, page_t.text, page_dt.string, page_dt.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue page_dt'
            . ' LEFT JOIN Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue page_t'
            . ' WITH page_t MEMBER OF page.titles AND page_t.localization = 123'
            . ' WHERE page_dt MEMBER OF page.titles AND page_dt.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                Page::class,
                'page'
            )
        );
    }
}
