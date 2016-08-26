<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\OrderBundle\Layout\Block\Extension\BlockPrefixExtension;

class BlockPrefixExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockPrefixExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new BlockPrefixExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testSetDefaultOptions()
    {
        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertArrayHasKey('block_prefixes', $options);
        $this->assertEquals($options['block_prefixes'], []);
    }

    public function testFinishView()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view = new BlockView();

        $this->extension->finishView($view, $block, new Options(['block_prefixes' => ['test_prefix']]));

        $this->assertArrayHasKey('block_prefixes', $view->vars);
        $this->assertEquals($view->vars['block_prefixes']->toArray(), ['test_prefix']);
    }

    public function testFinishViewWithoutOption()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view = new BlockView();

        $this->extension->finishView($view, $block, new Options());

        $this->assertArrayHasKey('block_prefixes', $view->vars);
        $this->assertEquals($view->vars['block_prefixes']->toArray(), []);
    }

    public function testFinishViewWithDefinedPrefixes()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view = new BlockView();
        $view->vars['block_prefixes'] = ['_prefix'];

        $this->extension->finishView($view, $block, new Options());

        $this->assertArrayHasKey('block_prefixes', $view->vars);
        $this->assertEquals($view->vars['block_prefixes']->toArray(), ['_prefix']);
    }
}
