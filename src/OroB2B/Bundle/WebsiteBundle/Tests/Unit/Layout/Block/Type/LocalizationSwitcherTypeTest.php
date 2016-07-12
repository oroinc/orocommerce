<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\WebsiteBundle\Layout\Block\Type\LocalizationSwitcherType;

class LocalizationSwitcherTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizationSwitcherType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new LocalizationSwitcherType();
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationSwitcherType::NAME, $this->type->getName());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['data']);

        $this->type->configureOptions($resolver);
    }

    public function testFinishView()
    {
        $view = new BlockView();

        /* @var $block BlockInterface|\PHPUnit_Framework_MockObject_MockObject */
        $block = $this->getMock(BlockInterface::class);

        $options = ['data' => ['localizations' => 'L1', 'L2'], 'current_localization' => 'L1'];
        $this->type->finishView($view, $block, $options);

        $this->assertArrayHasKey('data', $view->vars);
        $this->assertEquals($options['data'], $view->vars['data']);
    }
}
