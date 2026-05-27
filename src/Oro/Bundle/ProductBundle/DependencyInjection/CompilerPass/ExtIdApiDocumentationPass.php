<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the following modifications of "JSON:API EXT ID" API documentation:
 * * adds a description for the Product entity in the list of entities with an external system identifier
 * * sets "0RT28" as an example identifier for the "create" action of the Product entity
 * * remove "sku" attribute from a request example for the "create" action of the Product entity
 */
class ExtIdApiDocumentationPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_api.api_doc.documentation_provider.ext_id_integration_common_docs.resources')
            ->addMethodCall('setExtIdEntityDescription', [
                'Oro\Bundle\ProductBundle\Entity\Product',
                '(SKU is used as a product identifier)'
            ]);
        $container->getDefinition('oro_api.resource_doc_parser.ext_id_rest_json_api')
            ->addMethodCall('setIdValue', ['Oro\Bundle\ProductBundle\Entity\Product', '0RT28'])
            ->addMethodCall('setAttributesToRemove', ['Oro\Bundle\ProductBundle\Entity\Product', ['sku']]);
    }
}
