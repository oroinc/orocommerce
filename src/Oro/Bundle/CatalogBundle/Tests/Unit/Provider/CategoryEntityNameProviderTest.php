<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Provider\CategoryEntityNameProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;

class CategoryEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryEntityNameProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->provider = new CategoryEntityNameProvider();
    }

    public function testGetNameForShortFormat()
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new Category())
        );
    }

    public function testGetNameForUnsupportedEntity()
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForLocale()
    {
        $category = new Category();
        $category->addTitle($this->getFallbackValue('default title'))
            ->addTitle($this->getFallbackValue('localized title', $this->getLocalization('en')));

        $this->assertEquals(
            'default title',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $category)
        );
    }

    public function testGetNameForLocalization()
    {
        $localization = $this->getLocalization('en');

        $category = new Category();
        $category->addTitle($this->getFallbackValue('default title'))
            ->addTitle($this->getFallbackValue('localized title', $localization));

        $this->assertEquals(
            'localized title',
            $this->provider->getName(EntityNameProviderInterface::FULL, $localization, $category)
        );
    }

    public function testGetNameDQL()
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Category::class, 'category')
        );
    }

    /**
     * @param string $string
     * @param Localization|null $localization
     * @return CategoryTitle
     */
    protected function getFallbackValue($string, Localization $localization = null)
    {
        $value = new CategoryTitle();
        $value->setString($string)
            ->setLocalization($localization);

        return $value;
    }

    /**
     * @param string $code
     * @return Localization
     */
    protected function getLocalization($code)
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        $localization->setLanguage($language);

        return $localization;
    }
}
