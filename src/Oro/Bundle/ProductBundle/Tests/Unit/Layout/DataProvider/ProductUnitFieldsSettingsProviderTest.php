<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

class ProductUnitFieldsSettingsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitFieldsSettingsInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $unitVisibility;

    /**
     * @var ProductUnitFieldsSettingsProvider
     */
    private $provider;

    public function setUp()
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
