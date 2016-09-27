<?php

namespace Oro\Bundle\ShoppingListBundle;

use Oro\Bundle\ShoppingListBundle\DependencyInjection\CompilerPass\RfpProductCheckerPass;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroShoppingListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroShoppingListExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RfpProductCheckerPass());
    }
}
