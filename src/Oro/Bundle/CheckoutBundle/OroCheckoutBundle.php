<?php

namespace Oro\Bundle\CheckoutBundle;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Component\DependencyInjection\Compiler\InverseTaggedIteratorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The CheckoutBundle bundle class.
 */
class OroCheckoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\TwigSandboxConfigurationPass());
        $container->addCompilerPass(new InverseTaggedIteratorCompilerPass(
            'oro_checkout.workflow_state.mapper.registry.checkout_state_diff',
            'checkout.workflow_state.mapper'
        ));
        $container->addCompilerPass(new Compiler\CheckoutLineItemConverterPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroCheckoutExtension();
        }

        return $this->extension;
    }
}
