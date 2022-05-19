<?php

namespace Oro\Bundle\FedexShippingBundle;

use Oro\Bundle\FedexShippingBundle\DependencyInjection\Compiler\FedexRootDirPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class OroFedexShippingBundle extends Bundle
{
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // This is here because injecting '@kernel' or '@file_locator' into a service resulted in exception:
        // "You have requested a synthetic service ("kernel"). The DIC does not know how to construct this service."
        $container->addCompilerPass(new FedexRootDirPass($this->kernel->locateResource('@OroFedexShippingBundle')));
    }
}
