<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Provider\ProductEntityNameProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;

class ProductEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private ProductEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ProductEntityNameProvider();
    }

    private function getProductName(string $string, Localization $localization = null): ProductName
    {
        $value = new ProductName();
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

    public function testGetNameForShortFormat(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new Product())
        );
    }

    public function testGetName(): void
    {
        $product = new Product();
        $product->setSku('SKU');
        $product->addName($this->getProductName('default name'));
        $product->addName($this->getProductName('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'default name',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $product)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $product = new Product();
        $product->setSku('SKU');
        $product->addName($this->getProductName('default name'));
        $product->addName($this->getProductName('localized name', $this->getLocalization('en')));

        $this->assertEquals(
            'localized name',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $product)
        );
    }

    public function testGetNameForEmptyName(): void
    {
        $product = new Product();
        $product->setSku('SKU');
        $product->addName($this->getProductName(''));

        $this->assertEquals(
            'SKU',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $product)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQLForShortFormat(): void
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::SHORT, 'en', Product::class, 'product')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(NULLIF(product_n.string, \'\'), product.sku)'
            . ' FROM Oro\Bundle\ProductBundle\Entity\ProductName product_n'
            . ' WHERE product_n MEMBER OF product.names AND product_n.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, Product::class, 'product')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(product_n.string, NULLIF(product_dn.string, \'\'), product.sku)'
            . ' FROM Oro\Bundle\ProductBundle\Entity\ProductName product_dn'
            . ' LEFT JOIN Oro\Bundle\ProductBundle\Entity\ProductName product_n'
            . ' WITH product_n MEMBER OF product.names AND product_n.localization = 123'
            . ' WHERE product_dn MEMBER OF product.names AND product_dn.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                Product::class,
                'product'
            )
        );
    }
}
