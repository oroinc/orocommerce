<?php

namespace OroB2B\Bundle\CheckoutBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\CheckoutBundle\DependencyInjection\OroB2BCheckoutExtension;
use OroB2B\Bundle\CheckoutBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use OroB2B\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutCompilerPass;

class OroB2BCheckoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CheckoutCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BCheckoutExtension();
        }

        return $this->extension;
    }
}
