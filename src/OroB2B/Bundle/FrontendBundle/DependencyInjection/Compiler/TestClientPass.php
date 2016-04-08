<?php

namespace OroB2B\Bundle\FrontendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestClientPass implements CompilerPassInterface
{
    const TEST_CLIENT_CLASS = 'test.client.class';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('test.client.class', 'OroB2B\Bundle\FrontendBundle\Test\Client');
    }
}
