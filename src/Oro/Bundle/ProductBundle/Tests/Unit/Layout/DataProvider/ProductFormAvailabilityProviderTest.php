<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductFormAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productVariantAvailability;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var ProductFormAvailabilityProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->productVariantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductFormAvailabilityProvider(
            $this->productVariantAvailability,
            $this->configManager
        );
    }

    public function testIsInlineMatrixFormAvailable()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.matrix_form_on_product_view')
            ->willReturn('inline');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'id' => 123,
            'primaryUnitPrecision' => $unitPrecision,
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $simpleProduct = $this->getEntity(Product::class, ['id' => 321, 'primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1, 2]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(true, $this->provider->isInlineMatrixFormAvailable($product));

        // check caching
        $this->assertEquals(true, $this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsInlineMatrixFormAvailableReturnsFalseOnConfigOptionPopup()
    {
        $this->setInlineMatrixFormOption('popup');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->assertEquals(false, $this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsPopupMatrixFormAvailable()
    {
        $this->setInlineMatrixFormOption('popup');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'id' => 123,
            'primaryUnitPrecision' => $unitPrecision,
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1, 2]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(true, $this->provider->isPopupMatrixFormAvailable($product));
    }

    public function testIsPopupMatrixFormAvailableReturnsFalseOnConfigOptionInline()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->assertEquals(false, $this->provider->isPopupMatrixFormAvailable($product));
    }

    public function testIsSimpleFormAvailableWithConfigNone()
    {
        $this->setInlineMatrixFormOption('none');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->never())
            ->method('getVariantFieldsAvailability');

        $this->assertEquals(true, $this->provider->isSimpleFormAvailable($product));
    }

    public function testIsInlineMatrixFormAvailableReturnsFalseOnMoreThanTwoVariantFields()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['type' => Product::TYPE_CONFIGURABLE]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([[], [], []]);

        $this->assertEquals(false, $this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsInlineMatrixFormAvailableReturnsFalseOnUnitNotSupportedBySimpleProduct()
    {
        $this->setInlineMatrixFormOption('inline');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'primaryUnitPrecision' => $unitPrecision,
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $productUnit = $this->getEntity(ProductUnit::class);
        $productUnitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $productUnit]);

        /** @var Product $product */
        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $productUnitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1, 2]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(false, $this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsMatrixFormAvailableWithSimpleProduct()
    {
        /** @var Product $product */
        $simpleProduct = $this->getEntity(Product::class);
        $this->productVariantAvailability->expects($this->never())
            ->method('getVariantFieldsAvailability');

        $this->assertEquals(false, $this->provider->isMatrixFormAvailable($simpleProduct));
    }

    /**
     * @param string $value
     */
    private function setInlineMatrixFormOption($value)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.matrix_form_on_product_view')
            ->willReturn($value);
    }

    public function testIsInlineMatrixFormAvailableReturnsFalseWhenVariantsFieldIsLessThenTwo()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([]);

        $this->productVariantAvailability->expects($this->never())
            ->method('getSimpleProductsByVariantFields');

        $this->assertEquals(false, $this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsInlineMatrixFormAvailableWhenOneVariantField()
    {
        $this->setInlineMatrixFormOption('inline');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'id' => 123,
            'primaryUnitPrecision' => $unitPrecision,
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(true, $this->provider->isInlineMatrixFormAvailable($product));
    }

    public function testIsInlineMatrixFormAvailableReturnsFalseWithoutSimpleProducts()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class, [
            'type' => Product::TYPE_CONFIGURABLE
        ]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1, 2]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([]);

        $this->assertEquals(false, $this->provider->isInlineMatrixFormAvailable($product));
    }
}
