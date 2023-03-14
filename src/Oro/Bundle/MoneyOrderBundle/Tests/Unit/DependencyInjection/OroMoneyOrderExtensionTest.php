<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroMoneyOrderExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroMoneyOrderExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
