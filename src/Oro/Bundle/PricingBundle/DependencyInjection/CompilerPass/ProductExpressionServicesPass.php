<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services needed for working with expressions in price lists (expression parser, query builder,
 * field providers, etc.).
 */
class ProductExpressionServicesPass implements CompilerPassInterface
{
    const EXPRESSION_PARSER = 'oro_product.expression.parser';
    const QUERY_EXPRESSION_BUILDER = 'oro_product.expression.query_expression_builder';
    const EXPRESSION_PREPROCESSOR = 'oro_product.expression.preprocessor';
    const NODE_TO_QUERY_DESIGNER_CONVERTER = 'oro_product.expression.node_to_query_designer_converter';
    const QUERY_CONVERTER = 'oro_product.expression.query_converter';

    const ASSIGNED_PRODUCTS_CONVERTER = 'oro_pricing.expression.query_expression.converter.assigned_products';
    const ASSIGNMENT_RULE_PREPROCESSOR = 'oro_pricing.expression.preprocessor.product_assignment_rule';
    const PRICE_LIST_COLUMN_INFORMATION_PROVIDER = 'oro_pricing.expression.column_information.price_list_provider';
    const PRICE_LIST_QUERY_CONVERTER_EXTENSION = 'oro_pricing.expression.price_list_query_converter_extension';
    const FIELDS_PROVIDER = 'oro_product.expression.fields_provider';
    const AUTOCOMPLETE_FIELDS_PROVIDER = 'oro_product.autocomplete_fields_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EXPRESSION_PARSER)) {
            $container->getDefinition(self::EXPRESSION_PARSER)
                ->addMethodCall(
                    'addNameMapping',
                    ['pricelist', PriceList::class]
                );
        }

        if ($container->hasDefinition(self::QUERY_EXPRESSION_BUILDER)) {
            $container->getDefinition(self::QUERY_EXPRESSION_BUILDER)
                ->addMethodCall('registerConverter', [new Reference(self::ASSIGNED_PRODUCTS_CONVERTER), 10]);
        }

        if ($container->hasDefinition(self::EXPRESSION_PREPROCESSOR)) {
            $container->getDefinition(self::EXPRESSION_PREPROCESSOR)
                ->addMethodCall('registerPreprocessor', [new Reference(self::ASSIGNMENT_RULE_PREPROCESSOR)]);
        }

        if ($container->hasDefinition(self::NODE_TO_QUERY_DESIGNER_CONVERTER)) {
            $container->getDefinition(self::NODE_TO_QUERY_DESIGNER_CONVERTER)
                ->addMethodCall(
                    'addColumnInformationProvider',
                    [new Reference(self::PRICE_LIST_COLUMN_INFORMATION_PROVIDER)]
                );
        }

        if ($container->hasDefinition(self::QUERY_CONVERTER)) {
            $container->getDefinition(self::QUERY_CONVERTER)
                ->addMethodCall(
                    'addExtension',
                    [new Reference(self::PRICE_LIST_QUERY_CONVERTER_EXTENSION)]
                );
        }

        if ($container->hasDefinition(self::FIELDS_PROVIDER)) {
            $container->getDefinition(self::FIELDS_PROVIDER)
                ->addMethodCall(
                    'addFieldToWhiteList',
                    [PriceList::class, 'prices']
                );
        }

        if ($container->hasDefinition(self::AUTOCOMPLETE_FIELDS_PROVIDER)) {
            $container->getDefinition(self::AUTOCOMPLETE_FIELDS_PROVIDER)
                ->addMethodCall(
                    'addSpecialFieldInformation',
                    [
                        PriceList::class,
                        'assignedProducts',
                        [
                            'label' => 'oro.pricing.pricelist.assigned_products.label',
                            'type' => 'collection'
                        ]
                    ]
                )
                ->addMethodCall(
                    'addSpecialFieldInformation',
                    [
                        PriceList::class,
                        'productAssignmentRule',
                        [
                            'type' => 'standalone'
                        ]
                    ]
                );
        }
    }
}
