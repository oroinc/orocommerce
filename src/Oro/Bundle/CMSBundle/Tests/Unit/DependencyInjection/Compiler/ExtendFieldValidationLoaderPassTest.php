<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContent;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ExtendFieldValidationLoaderPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendFieldValidationLoaderPass */
    private $pass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->pass = new ExtendFieldValidationLoaderPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    public function testProcess(): void
    {
        $entityExtendValidationLoaderDefinition = new Definition();
        $serializedFieldsValidationLoaderDefinition = new Definition();

        $this->container
            ->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                ['oro_entity_extend.validation_loader'],
                ['oro_serialized_fields.validator.extend_entity_serialized_data']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                ['oro_entity_extend.validation_loader'],
                ['oro_serialized_fields.validator.extend_entity_serialized_data']
            )
            ->willReturnOnConsecutiveCalls(
                $entityExtendValidationLoaderDefinition,
                $serializedFieldsValidationLoaderDefinition
            );

        $this->pass->process($this->container);

        $this->assertEquals([
            ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]]
        ], $entityExtendValidationLoaderDefinition->getMethodCalls());
        $this->assertEquals([
            ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]]
        ], $serializedFieldsValidationLoaderDefinition->getMethodCalls());
    }

    public function testProcessWithoutEntityExtendValidationLoader(): void
    {
        $serializedFieldsValidationLoaderDefinition = new Definition();

        $this->container
            ->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                ['oro_entity_extend.validation_loader'],
                ['oro_serialized_fields.validator.extend_entity_serialized_data']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('oro_serialized_fields.validator.extend_entity_serialized_data')
            ->willReturn($serializedFieldsValidationLoaderDefinition);

        $this->pass->process($this->container);

        $this->assertEquals([
            ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]]
        ], $serializedFieldsValidationLoaderDefinition->getMethodCalls());
    }

    public function testProcessWithoutSerializedFieldsValidationLoader(): void
    {
        $entityExtendValidationLoaderDefinition = new Definition(null, ['', ['integer', 'boolean']]);

        $this->container
            ->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                ['oro_entity_extend.validation_loader'],
                ['oro_serialized_fields.validator.extend_entity_serialized_data']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('oro_entity_extend.validation_loader')
            ->willReturn($entityExtendValidationLoaderDefinition);

        $this->pass->process($this->container);

        $this->assertEquals([
            ['addConstraints', ['wysiwyg', [[TwigContent::class => null], [WYSIWYG::class => null]]]]
        ], $entityExtendValidationLoaderDefinition->getMethodCalls());
    }
}
