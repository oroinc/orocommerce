<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RuleBundle\DependencyInjection\OroRuleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRuleExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroRuleExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
