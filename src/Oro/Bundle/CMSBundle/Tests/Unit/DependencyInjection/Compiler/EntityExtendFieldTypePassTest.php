<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EntityExtendFieldTypePassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityExtendFieldTypePass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new EntityExtendFieldTypePass();
    }

    public function testProcessWithoutTargetServices()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $fieldTypeProviderDef = $container->register('oro_entity_extend.field_type_provider')
            ->setArguments(['', ['integer', 'boolean']]);
        $fieldGuesserDef = $container->register('oro_entity_extend.form.guesser.extend_field');

        $this->compiler->process($container);

        $this->assertEquals(
            ['integer', 'boolean', 'wysiwyg'],
            $fieldTypeProviderDef->getArgument(1)
        );
        $this->assertEquals(
            [
                ['addExtendTypeMapping', ['wysiwyg', WYSIWYGType::class]]
            ],
            $fieldGuesserDef->getMethodCalls()
        );
    }
}
