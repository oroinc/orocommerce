<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Provider\BrandEntityNameProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Brand;
use Oro\Bundle\TranslationBundle\Entity\Language;

class BrandEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var BrandEntityNameProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new BrandEntityNameProvider();
    }

    public function testGetNameForShortFormat()
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new Brand())
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
        $brand = new Brand();
        $brand->addName($this->getFallbackValue('default name'))
            ->addName($this->getFallbackValue('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'default name',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $brand)
        );
    }

    public function testGetNameForLocalization()
    {
        $localization = $this->getLocalization('en');

        $brand = new Brand();
        $brand->addName($this->getFallbackValue('default name'))
            ->addName($this->getFallbackValue('localized name', $localization));

        $this->assertEquals(
            'localized name',
            $this->provider->getName(EntityNameProviderInterface::FULL, $localization, $brand)
        );
    }

    public function testGetNameDQL()
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Brand::class, 'brand')
        );
    }

    /**
     * @param string $string
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    protected function getFallbackValue($string, Localization $localization = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)->setLocalization($localization);

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
