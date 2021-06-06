<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ProductExpressionServicesPass;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProductExpressionServicesPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductExpressionServicesPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ProductExpressionServicesPass();
    }

    public function testNoExtensionsAreAddedWhenDefinitionsAreAbsent()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testAddPriceListNameMapping()
    {
        $container = new ContainerBuilder();
        $expressionParserDef = $container->register('oro_product.expression.parser');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addNameMapping', ['pricelist', PriceList::class]]
            ],
            $expressionParserDef->getMethodCalls()
        );
    }

    public function testRegisterAssignedProductsConverter()
    {
        $container = new ContainerBuilder();
        $expressionBuilderDef = $container->register('oro_product.expression.query_expression_builder');

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'registerConverter',
                    [new Reference('oro_pricing.expression.query_expression.converter.assigned_products'), 10]
                ]
            ],
            $expressionBuilderDef->getMethodCalls()
        );
    }

    public function testRegisterPreprocessor()
    {
        $container = new ContainerBuilder();
        $expressionPreprocessorDef = $container->register('oro_product.expression.preprocessor');

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'registerPreprocessor',
                    [new Reference('oro_pricing.expression.preprocessor.product_assignment_rule')]
                ]
            ],
            $expressionPreprocessorDef->getMethodCalls()
        );
    }

    public function testNodeToQueryDesignerConverter()
    {
        $container = new ContainerBuilder();
        $expressionConverterDef = $container->register('oro_product.expression.node_to_query_designer_converter');

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'addColumnInformationProvider',
                    [new Reference('oro_pricing.expression.column_information.price_list_provider')]
                ]
            ],
            $expressionConverterDef->getMethodCalls()
        );
    }

    public function testQueryConverter()
    {
        $container = new ContainerBuilder();
        $expressionConverterDef = $container->register('oro_product.expression.query_converter');

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'addExtension',
                    [new Reference('oro_pricing.expression.price_list_query_converter_extension')]
                ]
            ],
            $expressionConverterDef->getMethodCalls()
        );
    }

    public function testFieldsProvider()
    {
        $container = new ContainerBuilder();
        $expressionFieldsProviderDef = $container->register('oro_product.expression.fields_provider');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addFieldToWhiteList', [PriceList::class, 'prices']]
            ],
            $expressionFieldsProviderDef->getMethodCalls()
        );
    }

    public function testAutocompleteFieldsProvider()
    {
        $container = new ContainerBuilder();
        $autocompleteFieldsProviderDef = $container->register('oro_product.autocomplete_fields_provider');

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'addSpecialFieldInformation',
                    [
                        PriceList::class,
                        'assignedProducts',
                        ['label' => 'oro.pricing.pricelist.assigned_products.label', 'type' => 'collection']
                    ]
                ],
                [
                    'addSpecialFieldInformation',
                    [
                        PriceList::class,
                        'productAssignmentRule',
                        ['type' => 'standalone']
                    ]
                ]
            ],
            $autocompleteFieldsProviderDef->getMethodCalls()
        );
    }
}
