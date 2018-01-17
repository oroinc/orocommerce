<?php

namespace Oro\Bundle\FedexShippingBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class OroFedexShippingBundle extends Bundle
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // This is here because injecting '@kernel' or '@file_locator' into a service resulted in exception:
        // "You have requested a synthetic service ("kernel"). The DIC does not know how to construct this service."
        $container->setParameter('fedex_root_dir', $this->kernel->locateResource('@OroFedexShippingBundle'));
    }
}
