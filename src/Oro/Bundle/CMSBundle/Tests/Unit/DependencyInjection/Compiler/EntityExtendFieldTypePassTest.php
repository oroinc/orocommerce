<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EntityExtendFieldTypePassTest extends \PHPUnit\Framework\TestCase
{
    private EntityExtendFieldTypePass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new EntityExtendFieldTypePass();
    }

    public function testProcessWithoutTargetServices(): void
    {
        $container = new ContainerBuilder();

        $this->expectNotToPerformAssertions();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $fieldTypeProviderDef = $container->register('oro_entity_extend.field_type_provider')
            ->setArguments(['', ['integer', 'boolean']]);
        $fieldGuesserDef = $container->register('oro_entity_extend.provider.extend_field_form_type');

        $this->compiler->process($container);

        self::assertEquals(
            ['integer', 'boolean', 'wysiwyg'],
            $fieldTypeProviderDef->getArgument(1)
        );
        self::assertEquals(
            [
                ['addExtendTypeMapping', ['wysiwyg', WYSIWYGType::class]],
            ],
            $fieldGuesserDef->getMethodCalls()
        );
    }
}
