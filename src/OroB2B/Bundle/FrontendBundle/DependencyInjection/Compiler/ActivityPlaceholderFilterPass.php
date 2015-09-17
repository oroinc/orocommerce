<?php

namespace OroB2B\Bundle\FrontendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ActivityPlaceholderFilterPass implements CompilerPassInterface
{
    const FILTER_SERVICE = 'oro_activity_list.placeholder.filter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::FILTER_SERVICE)) {
            return;
        }

        $filterDefinition = $container->getDefinition(self::FILTER_SERVICE);

        $filterDefinition->setClass($container->getParameter('orob2b_frontend.activity_list.placeholder.filter.class'));

        $filterDefinition->addMethodCall(
            'setHelper',
            [
                $container->getDefinition('orob2b_frontend.request.frontend_helper'),
            ]
        );

        $filterDefinition->addMethodCall(
            'setRequestStack',
            [
                $container->getDefinition('request_stack'),
            ]
        );
    }
}
