<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Twig\ProductUnitFieldsSettingsExtension;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

class ProductUnitFieldsSettingsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    protected $productUnitFieldsSettings;

    /**
     * @var ProductUnitFieldsSettingsExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->productUnitFieldsSettings = $this->createMock(ProductUnitFieldsSettingsInterface::class);
        $this->extension = new ProductUnitFieldsSettingsExtension($this->productUnitFieldsSettings);
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            [
                'oro_is_product_unit_selection_visible',
                [$this->productUnitFieldsSettings, 'isProductUnitSelectionVisible']
            ],
            [
                'oro_is_product_primary_unit_visible',
                [$this->productUnitFieldsSettings, 'isProductPrimaryUnitVisible']
            ],
            [
                'oro_is_adding_additional_units_to_product_available',
                [$this->productUnitFieldsSettings, 'isAddingAdditionalUnitsToProductAvailable']
            ],
        ];
        /** @var \Twig_SimpleFunction[] $actualFunctions */
        $actualFunctions = $this->extension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals($expectedFunction[1], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }
}
