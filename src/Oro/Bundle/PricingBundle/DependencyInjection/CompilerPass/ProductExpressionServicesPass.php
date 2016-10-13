<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProductExpressionServicesPass implements CompilerPassInterface
{
    const EXPRESSION_PARSER = 'oro_product.expression.parser';
    const QUERY_EXPRESSION_BUILDER = 'oro_product.expression.query_expression_builder';
    const EXPRESSION_PREPROCESSOR = 'oro_product.expression.preprocessor';
    const NODE_TO_QUERY_DESIGNER_CONVERTER = 'oro_product.expression.node_to_query_designer_converter';

    const ASSIGNED_PRODUCTS_CONVERTER = 'oro_pricing.expression.query_expression.converter.assigned_products';
    const ASSIGNMENT_RULE_PREPROCESSOR = 'oro_pricing.expression.preprocessor.product_assignment_rule';
    const PRICE_LIST_COLUMN_INFORMATION_PROVIDER = 'oro_pricing.expression.column_information.price_list_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EXPRESSION_PARSER)) {
            $container->getDefinition(self::EXPRESSION_PARSER)
                ->addMethodCall(
                    'addNameMapping',
                    ['pricelist', $container->getParameter('oro_pricing.entity.price_list.class')]
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
    }
}
