<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Provider\BrandEntityNameProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Brand;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;

class BrandEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private BrandEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new BrandEntityNameProvider();
    }

    private function getBrandName(string $string, ?Localization $localization = null): LocalizedFallbackValue
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
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $brand = new Brand();
        $brand->addName($this->getBrandName('default name'));
        $brand->addName($this->getBrandName('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'default name',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $brand)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $brand = new Brand();
        $brand->addName($this->getBrandName('default name'));
        $brand->addName($this->getBrandName('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'localized name',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $brand)
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
            'CAST((SELECT COALESCE(brand_n.string, brand_n.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue brand_n'
            . ' WHERE brand_n MEMBER OF brand.names AND brand_n.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, Brand::class, 'brand')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(brand_n.string, brand_n.text, brand_dn.string, brand_dn.text)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue brand_dn'
            . ' LEFT JOIN Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue brand_n'
            . ' WITH brand_n MEMBER OF brand.names AND brand_n.localization = 123'
            . ' WHERE brand_dn MEMBER OF brand.names AND brand_dn.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                Brand::class,
                'brand'
            )
        );
    }
}
