<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductFormAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productVariantAvailability;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /** @var ProductFormAvailabilityProvider */
    private $provider;

    protected function setUp()
    {
        $this->productVariantAvailability = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductFormAvailabilityProvider(
            $this->productVariantAvailability,
            $this->configManager
        );
    }

    public function testIsInlineMatrixAvailable()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.matrix_form_on_product_view')
            ->willReturn('inline');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 123, 'primaryUnitPrecision' => $unitPrecision]);
        $simpleProduct = $this->getEntity(Product::class, ['id' => 321, 'primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1, 2]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(true, $this->provider->isInlineMatrixAvailable($product));

        // check caching
        $this->assertEquals(true, $this->provider->isInlineMatrixAvailable($product));
    }

    public function testIsInlineMatrixAvailableReturnsFalseOnConfigOptionPopup()
    {
        $this->setInlineMatrixFormOption('popup');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->assertEquals(false, $this->provider->isInlineMatrixAvailable($product));
    }

    public function testIsPopupMatrixAvailable()
    {
        $this->setInlineMatrixFormOption('popup');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);
        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([1, 2]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(true, $this->provider->isPopupMatrixAvailable($product));
    }

    public function testIsPopupMatrixAvailableReturnsFalseOnConfigOptionInline()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->assertEquals(false, $this->provider->isPopupMatrixAvailable($product));
    }

    public function testIsSimpleAvailableWithConfigNone()
    {
        $this->setInlineMatrixFormOption('none');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->never())
            ->method('getVariantFieldsAvailability');

        $this->assertEquals(true, $this->provider->isSimpleAvailable($product));
    }

    public function testIsSimpleAvailableWithConfigInline()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willThrowException(new \InvalidArgumentException());

        $this->assertEquals(true, $this->provider->isSimpleAvailable($product));
    }

    public function testIsInlineMatrixAvailableReturnsFalseOnSimpleProduct()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willThrowException(new \InvalidArgumentException());

        $this->assertEquals(false, $this->provider->isInlineMatrixAvailable($product));
    }

    public function testIsInlineMatrixAvailableReturnsFalseOnMoreThanTwoVariantFields()
    {
        $this->setInlineMatrixFormOption('inline');

        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([[], [], []]);

        $this->assertEquals(false, $this->provider->isInlineMatrixAvailable($product));
    }

    public function testIsInlineMatrixAvailableReturnsFalseOnUnitNotSupportedBySimpleProduct()
    {
        $this->setInlineMatrixFormOption('inline');

        $unit = $this->getEntity(ProductUnit::class);
        $unitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $unit]);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['primaryUnitPrecision' => $unitPrecision]);

        $productUnit = $this->getEntity(ProductUnit::class);
        $productUnitPrecision = $this->getEntity(ProductUnitPrecision::class, ['unit' => $productUnit]);

        /** @var Product $product */
        $simpleProduct = $this->getEntity(Product::class, ['primaryUnitPrecision' => $productUnitPrecision]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getVariantFieldsAvailability')
            ->with($product)
            ->willReturn([[1, 2]]);

        $this->productVariantAvailability->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([$simpleProduct]);

        $this->assertEquals(false, $this->provider->isInlineMatrixAvailable($product));
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
}
