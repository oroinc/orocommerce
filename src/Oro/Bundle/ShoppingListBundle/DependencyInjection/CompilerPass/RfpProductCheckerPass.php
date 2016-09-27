<?php

namespace Oro\Bundle\ShoppingListBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RfpProductCheckerPass implements CompilerPassInterface
{
    const RFP_PRODUCT_CHECKER_SERVICE_ID = 'oro_rfp.form.type.extension.frontend_request_data_storage';
    const SERVICE_ID = 'oro_shopping_list.rfp_product_checker';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::RFP_PRODUCT_CHECKER_SERVICE_ID)) {
            return;
        }

        $container->setDefinition(self::SERVICE_ID, $container->getDefinition(self::RFP_PRODUCT_CHECKER_SERVICE_ID));
    }
}
