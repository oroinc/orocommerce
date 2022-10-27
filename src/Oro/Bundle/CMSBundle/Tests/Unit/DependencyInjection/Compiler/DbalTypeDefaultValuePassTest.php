<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\DbalTypeDefaultValuePass;
use Oro\Bundle\PlatformBundle\Provider\DbalTypeDefaultValueProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DbalTypeDefaultValuePassTest extends \PHPUnit\Framework\TestCase
{
    private DbalTypeDefaultValuePass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new DbalTypeDefaultValuePass();
    }

    public function testProcessDoesNothingWhenNoProvider(): void
    {
        $this->expectNotToPerformAssertions();

        $this->compiler->process(new ContainerBuilder());
    }

    public function testProcessAddsDefaultValues(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'oro_platform.provider.dbal_type_default_value',
            new Definition(DbalTypeDefaultValueProvider::class)
        );

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'addDefaultValuesForDbalTypes',
                    [[WYSIWYGType::TYPE => '', WYSIWYGStyleType::TYPE => '', WYSIWYGPropertiesType::TYPE => '[]']],
                ],
            ],
            $container->getDefinition('oro_platform.provider.dbal_type_default_value')->getMethodCalls()
        );
    }
}
