<?php

namespace Oro\Bundle\FedexShippingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines FedEx root dir container parameter.
 */
class FedexRootDirPass implements CompilerPassInterface
{
    /** @var string */
    private $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('fedex_root_dir', $this->rootDir);
    }
}
