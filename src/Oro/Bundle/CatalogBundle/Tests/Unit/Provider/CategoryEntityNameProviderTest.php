<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Provider\CategoryEntityNameProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;

class CategoryEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private CategoryEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new CategoryEntityNameProvider();
    }

    private function getCategoryTitle(string $string, ?Localization $localization = null): CategoryTitle
    {
        $value = new CategoryTitle();
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
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $category = new Category();
        $category->addTitle($this->getCategoryTitle('default title'));
        $category->addTitle($this->getCategoryTitle('localized title', $this->getLocalization('en')));

        $this->assertEquals(
            'default title',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $category)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $category = new Category();
        $category->addTitle($this->getCategoryTitle('default title'));
        $category->addTitle($this->getCategoryTitle('localized title', $this->getLocalization('en')));

        $this->assertEquals(
            'localized title',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $category)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT category_t.string'
            . ' FROM Oro\Bundle\CatalogBundle\Entity\CategoryTitle category_t'
            . ' WHERE category_t MEMBER OF category.titles AND category_t.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, Category::class, 'category')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(category_t.string, category_dt.string)'
            . ' FROM Oro\Bundle\CatalogBundle\Entity\CategoryTitle category_dt'
            . ' LEFT JOIN Oro\Bundle\CatalogBundle\Entity\CategoryTitle category_t'
            . ' WITH category_t MEMBER OF category.titles AND category_t.localization = 123'
            . ' WHERE category_dt MEMBER OF category.titles AND category_dt.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                Category::class,
                'category'
            )
        );
    }
}
