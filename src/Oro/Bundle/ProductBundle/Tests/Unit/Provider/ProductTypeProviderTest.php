<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;

class ProductTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getAvailableProductTypesDataProvider
     */
    public function testGetAvailableProductTypes(array $availableProductTypes, array $expectedProductTypesChoices): void
    {
        $productTypeProvider = new ProductTypeProvider($availableProductTypes);

        self::assertEquals($expectedProductTypesChoices, $productTypeProvider->getAvailableProductTypes());
    }

    public function getAvailableProductTypesDataProvider(): array
    {
        return [
            'default' => [
                'availableProductTypes' => Product::getTypes(),
                'expectedProductTypesChoices' => [
                    'oro.product.type.simple' => 'simple',
                    'oro.product.type.configurable' => 'configurable',
                    'oro.product.type.kit' => 'kit',
                ],
            ],
            'custom' => [
                'availableProductTypes' => [
                    'simple',
                    'configurable',
                ],
                'expectedProductTypesChoices' => [
                    'oro.product.type.simple' => 'simple',
                    'oro.product.type.configurable' => 'configurable',
                ],
            ],
        ];
    }
}
