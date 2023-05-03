<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getProcessConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configuration, array $expectedProductTypes): void
    {
        $processedConfig = $this->processConfiguration($configuration);

        self::assertEquals($expectedProductTypes, $processedConfig[Configuration::PRODUCT_TYPES]);
    }

    public function getProcessConfigurationDataProvider(): array
    {
        return [
            'default' => [
                'configuration' => [],
                'expectedProductTypes' => Product::getTypes(),
            ],
            'custom configuration' => [
                'configuration' => [
                    Configuration::ROOT_NODE => [
                        Configuration::PRODUCT_TYPES => [
                            'simple',
                            'configurable',
                        ],
                    ],
                ],
                'expectedProductTypes' => [
                    'simple',
                    'configurable',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getProcessConfigurationNotAllowedProductTypesDataProvider
     */
    public function testProcessConfigurationNotAllowedProductTypes(
        string|array|null $productTypes,
        string $expectedExceptionMessage
    ): void {
        $this->expectExceptionObject(new InvalidConfigurationException($expectedExceptionMessage));

        $this->processConfiguration([
            Configuration::ROOT_NODE => [
                Configuration::PRODUCT_TYPES => $productTypes,
            ]
        ]);
    }

    public function getProcessConfigurationNotAllowedProductTypesDataProvider(): array
    {
        $notAllowedProductType = 'unsupported';

        $atLeast1ElementMessage = 'The path "oro_product.product_types" should have at least 1 element(s) defined.';
        $invalidTypeMessage = 'Invalid type for path "oro_product.product_types". Expected "array", but got "string"';
        $notAllowedProductTypeMessage = sprintf(
            'Invalid configuration for path "oro_product.product_types.0": Not allowed product type "%s"',
            $notAllowedProductType
        );

        return [
            'not array' => [
                'productTypes' => 'value',
                'expectedExceptionMessage' => $invalidTypeMessage,
            ],
            'null' => [
                'productTypes' => null,
                'expectedExceptionMessage' => $atLeast1ElementMessage,
            ],
            'empty array' => [
                'productTypes' => [],
                'expectedExceptionMessage' => $atLeast1ElementMessage,
            ],
            'not allowed product type' => [
                'productTypes' => [
                    $notAllowedProductType,
                ],
                'expectedExceptionMessage' => $notAllowedProductTypeMessage,
            ],
        ];
    }

    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }
}
