<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutLineItemConverterPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class CheckoutLineItemConverterPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;
    private Definition $registry;
    private CheckoutLineItemConverterPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->registry = $this->container->register('oro_checkout.line_item.converter_registry')
            ->addArgument([]);

        $this->compiler = new CheckoutLineItemConverterPass();
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $this->compiler->process($this->container);

        /** @var IteratorArgument $iteratorArgument */
        $iteratorArgument = $this->registry->getArgument('$converters');
        self::assertInstanceOf(IteratorArgument::class, $iteratorArgument);
        self::assertEquals([], $iteratorArgument->getValues());
    }

    public function testProcessWithoutAliasAttribute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "alias" is required for "oro.checkout.line_item.converter" tag. Service: "converter_1".'
        );

        $this->container->setDefinition('converter_1', new Definition())
            ->addTag('oro.checkout.line_item.converter');

        $this->compiler->process($this->container);
    }

    public function testProcess(): void
    {
        $this->container->setDefinition('converter_1', new Definition())
            ->addTag('oro.checkout.line_item.converter', ['alias' => 'item1']);
        $this->container->setDefinition('converter_2', new Definition())
            ->addTag('oro.checkout.line_item.converter', ['alias' => 'item2', 'priority' => -10]);
        $this->container->setDefinition('converter_3', new Definition())
            ->addTag('oro.checkout.line_item.converter', ['alias' => 'item3', 'priority' => 10]);
        // override by alias
        $this->container->setDefinition('converter_4', new Definition())
            ->addTag('oro.checkout.line_item.converter', ['alias' => 'item1', 'priority' => -10]);
        // should be skipped by priority
        $this->container->setDefinition('converter_5', new Definition())
            ->addTag('oro.checkout.line_item.converter', ['alias' => 'item2']);

        $this->compiler->process($this->container);

        /** @var IteratorArgument $iteratorArgument */
        $iteratorArgument = $this->registry->getArgument('$converters');
        self::assertInstanceOf(IteratorArgument::class, $iteratorArgument);
        self::assertEquals(
            [
                new Reference('converter_2'),
                new Reference('converter_4'),
                new Reference('converter_3')
            ],
            $iteratorArgument->getValues()
        );
    }
}
