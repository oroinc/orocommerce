<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ProductExpressionServicesPass;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ProductExpressionServicesPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductExpressionServicesPass
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $container;

    protected function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->compilerPass = new ProductExpressionServicesPass();
    }

    public function testNoExtensionsAreAddedWhenDefinitionsAreAbsent()
    {
        $this->container->expects($this->exactly(7))
            ->method('hasDefinition')
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testAddPriceListNameMapping()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::EXPRESSION_PARSER, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addNameMapping', ['pricelist', PriceList::class]);

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::EXPRESSION_PARSER, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testRegisterAssignedProductsConverter()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::QUERY_EXPRESSION_BUILDER, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'registerConverter',
                [new Reference(ProductExpressionServicesPass::ASSIGNED_PRODUCTS_CONVERTER), 10]
            );

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::QUERY_EXPRESSION_BUILDER, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testRegisterPreprocessor()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::EXPRESSION_PREPROCESSOR, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'registerPreprocessor',
                [new Reference(ProductExpressionServicesPass::ASSIGNMENT_RULE_PREPROCESSOR)]
            );

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::EXPRESSION_PREPROCESSOR, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testNodeToQueryDesignerConverter()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::NODE_TO_QUERY_DESIGNER_CONVERTER, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addColumnInformationProvider',
                [new Reference(ProductExpressionServicesPass::PRICE_LIST_COLUMN_INFORMATION_PROVIDER)]
            );

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::NODE_TO_QUERY_DESIGNER_CONVERTER, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testQueryConverter()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::QUERY_CONVERTER, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addExtension',
                [new Reference(ProductExpressionServicesPass::PRICE_LIST_QUERY_CONVERTER_EXTENSION)]
            );

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::QUERY_CONVERTER, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testFieldsProvider()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::FIELDS_PROVIDER, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addFieldToWhiteList',
                [PriceList::class, 'prices']
            );

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::FIELDS_PROVIDER, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testAutocompleteFieldsProvider()
    {
        $this->container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::AUTOCOMPLETE_FIELDS_PROVIDER, true]
                ]
            );

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                [
                    'addSpecialFieldInformation',
                    [
                        PriceList::class,
                        'assignedProducts',
                        [
                            'label' => 'oro.pricing.pricelist.assigned_products.label',
                            'type' => 'collection'
                        ]
                    ]
                ],
                [
                    'addSpecialFieldInformation',
                    [
                        PriceList::class,
                        'productAssignmentRule',
                        [
                            'type' => 'standalone'
                        ]
                    ]
                ]
            )->willReturnSelf();

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [ProductExpressionServicesPass::AUTOCOMPLETE_FIELDS_PROVIDER, $definition]
                ]
            );

        $this->compilerPass->process($this->container);
    }
}
