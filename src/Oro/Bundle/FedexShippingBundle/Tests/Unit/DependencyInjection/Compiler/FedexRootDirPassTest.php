<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FedexShippingBundle\DependencyInjection\Compiler\FedexRootDirPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FedexRootDirPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $rootDir = '/root/dir';
        $compilerPass = new FedexRootDirPass($rootDir);

        $container = new ContainerBuilder();
        $compilerPass->process($container);

        $this->assertEquals($rootDir, $container->getParameter('fedex_root_dir'));
    }
}
