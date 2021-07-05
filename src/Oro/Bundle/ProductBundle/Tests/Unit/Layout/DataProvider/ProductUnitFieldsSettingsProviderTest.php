<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductUnitFieldsSettingsProvider;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

class ProductUnitFieldsSettingsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductUnitFieldsSettingsInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $unitVisibility;

    /**
     * @var ProductUnitFieldsSettingsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->unitVisibility = $this->createMock(ProductUnitFieldsSettingsInterface::class);
        $this->provider = new ProductUnitFieldsSettingsProvider($this->unitVisibility);
    }

    public function testIsProductUnitSelectionVisible()
    {
        $product = $this->createMock(Product::class);

        $this->unitVisibility->expects($this->once())
            ->method('isProductUnitSelectionVisible')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->provider->isProductUnitSelectionVisible($product));
    }
}
