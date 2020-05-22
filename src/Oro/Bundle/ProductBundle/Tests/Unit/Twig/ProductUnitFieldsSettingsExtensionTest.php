<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Twig\ProductUnitFieldsSettingsExtension;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUnitFieldsSettingsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $productUnitFieldsSettings;

    /** @var ProductUnitFieldsSettingsExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->productUnitFieldsSettings = $this->createMock(ProductUnitFieldsSettingsInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.visibility.product_unit_fields_settings', $this->productUnitFieldsSettings)
            ->getContainer($this);

        $this->extension = new ProductUnitFieldsSettingsExtension($container);
    }

    public function testIsProductUnitSelectionVisible()
    {
        $product = new Product();

        $this->productUnitFieldsSettings->expects(self::once())
            ->method('isProductUnitSelectionVisible')
            ->with(self::identicalTo($product))
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'oro_is_product_unit_selection_visible', [$product])
        );
    }

    public function testIsProductPrimaryUnitVisible()
    {
        $product = new Product();

        $this->productUnitFieldsSettings->expects(self::once())
            ->method('isProductPrimaryUnitVisible')
            ->with(self::identicalTo($product))
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'oro_is_product_primary_unit_visible', [$product])
        );
    }

    public function testIsAddingAdditionalUnitsToProductAvailable()
    {
        $product = new Product();

        $this->productUnitFieldsSettings->expects(self::once())
            ->method('isAddingAdditionalUnitsToProductAvailable')
            ->with(self::identicalTo($product))
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction(
                $this->extension,
                'oro_is_adding_additional_units_to_product_available',
                [$product]
            )
        );
    }
}
