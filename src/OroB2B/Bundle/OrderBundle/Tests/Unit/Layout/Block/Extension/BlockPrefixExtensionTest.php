<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

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
        $this->extension->setDefaultOptions($resolver);

        $options = $resolver->resolve();

        $this->assertArrayHasKey('block_prefixes', $options);
        $this->assertEquals($options['block_prefixes'], []);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage "block_prefixes" with value null is expected to be of type "array"
     */
    public function testSetDefaultOptionsFailed()
    {
        $resolver = new OptionsResolver();
        $this->extension->setDefaultOptions($resolver);

        $options = $resolver->resolve(['block_prefixes' => null]);

        $this->assertArrayHasKey('block_prefixes', $options);
        $this->assertEquals($options['block_prefixes'], []);
    }

    public function testFinishView()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view = new BlockView();

        $this->extension->finishView($view, $block, ['block_prefixes' => ['test_prefix']]);

        $this->assertArrayHasKey('block_prefixes', $view->vars);
        $this->assertEquals($view->vars['block_prefixes'], ['test_prefix']);
    }

    public function testFinishViewWithoutOption()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view = new BlockView();

        $this->extension->finishView($view, $block, []);

        $this->assertArrayHasKey('block_prefixes', $view->vars);
        $this->assertEquals($view->vars['block_prefixes'], []);
    }

    public function testFinishViewWithDefinedPrefixes()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view = new BlockView();
        $view->vars['block_prefixes'] = ['_prefix'];

        $this->extension->finishView($view, $block, []);

        $this->assertArrayHasKey('block_prefixes', $view->vars);
        $this->assertEquals($view->vars['block_prefixes'], ['_prefix']);
    }
}
