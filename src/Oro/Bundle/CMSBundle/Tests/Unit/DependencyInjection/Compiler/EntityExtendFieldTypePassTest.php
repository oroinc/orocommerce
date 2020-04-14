<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EntityExtendFieldTypePassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityExtendFieldTypePass */
    private $pass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->pass = new EntityExtendFieldTypePass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    public function testProcess()
    {
        $fieldTypeProviderDefinition = new Definition(null, ['', ['integer', 'boolean']]);
        $fieldGuesserDefinition = new Definition();

        $this->container
            ->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                ['oro_entity_extend.field_type_provider'],
                ['oro_entity_extend.form.guesser.extend_field']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                ['oro_entity_extend.field_type_provider'],
                ['oro_entity_extend.form.guesser.extend_field']
            )
            ->willReturnOnConsecutiveCalls(
                $fieldTypeProviderDefinition,
                $fieldGuesserDefinition
            );

        $this->pass->process($this->container);

        $this->assertEquals(['integer', 'boolean', 'wysiwyg'], $fieldTypeProviderDefinition->getArgument(1));
        $this->assertEquals([
            ['addExtendTypeMapping', ['wysiwyg', WYSIWYGType::class]]
        ], $fieldGuesserDefinition->getMethodCalls());
    }

    public function testProcessWithoutFieldTypeProvider()
    {
        $fieldGuesserDefinition = new Definition();

        $this->container
            ->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                ['oro_entity_extend.field_type_provider'],
                ['oro_entity_extend.form.guesser.extend_field']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('oro_entity_extend.form.guesser.extend_field')
            ->willReturn($fieldGuesserDefinition);

        $this->pass->process($this->container);

        $this->assertEquals([
            ['addExtendTypeMapping', ['wysiwyg', WYSIWYGType::class]]
        ], $fieldGuesserDefinition->getMethodCalls());
    }

    public function testProcessWithoutFieldGuesser()
    {
        $fieldTypeProviderDefinition = new Definition(null, ['', ['integer', 'boolean']]);

        $this->container
            ->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                ['oro_entity_extend.field_type_provider'],
                ['oro_entity_extend.form.guesser.extend_field']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('oro_entity_extend.field_type_provider')
            ->willReturn($fieldTypeProviderDefinition);

        $this->pass->process($this->container);

        $this->assertEquals(['integer', 'boolean', 'wysiwyg'], $fieldTypeProviderDefinition->getArgument(1));
    }
}
