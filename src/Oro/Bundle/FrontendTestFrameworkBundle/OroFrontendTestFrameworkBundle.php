<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;

class OroFrontendTestFrameworkBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('test.client.class', Client::class);
    }
}
