<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFrontendTestFrameworkBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('test.client.class', Client::class);
    }
}
