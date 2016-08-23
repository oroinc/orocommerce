<?php

namespace Oro\Bundle\FrontendBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExceptionControllerCompilerPass implements CompilerPassInterface
{
    const CONTROLLER_PARAMETER = 'twig.exception_listener.controller';
    const CONTROLLER_VALUE = 'Oro\Bundle\FrontendBundle\Controller\ExceptionController::showAction';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(static::CONTROLLER_PARAMETER)) {
            $container->setParameter(static::CONTROLLER_PARAMETER, static::CONTROLLER_VALUE);
        }
    }
}
