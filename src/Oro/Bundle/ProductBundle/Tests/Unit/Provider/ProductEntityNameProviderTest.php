<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Provider\ProductEntityNameProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\TranslationBundle\Entity\Language;

class ProductEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductEntityNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductEntityNameProvider();
    }

    public function testGetNameForShortFormat()
    {
        $this->assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new Product()));
        $this->assertFalse($this->provider->getName(null, 'en', new Product()));
    }

    public function testGetNameForUnsupportedEntity()
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForLocale()
    {
        $product = new Product();
        $product
            ->addName($this->getFallbackValue('default name'))
            ->addName($this->getFallbackValue('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'default name',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $product)
        );
    }

    public function testGetNameForLocalization()
    {
        $product = new Product();
        $product
            ->addName($this->getFallbackValue('default name'))
            ->addName($this->getFallbackValue('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'localized name',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $product)
        );
    }

    public function testGetNameDQL()
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Product::class, 'product')
        );
    }

    /**
     * @param string $string
     * @param Localization|null $localization
     * @return ProductName
     */
    protected function getFallbackValue($string, Localization $localization = null)
    {
        $value = new ProductName();
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
