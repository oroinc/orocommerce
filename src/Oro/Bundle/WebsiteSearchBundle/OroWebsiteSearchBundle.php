<?php

namespace Oro\Bundle\WebsiteSearchBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroWebsiteSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new WebsiteSearchCompilerPass());

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                ['Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity'],
                [$this->getPath() . DIRECTORY_SEPARATOR . 'SearchResult' . DIRECTORY_SEPARATOR . 'Entity']
            )
        );
    }
}
