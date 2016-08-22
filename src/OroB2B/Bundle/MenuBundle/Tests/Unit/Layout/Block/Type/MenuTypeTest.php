<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

use OroB2B\Bundle\MenuBundle\Layout\Block\Type\MenuType;

class MenuTypeTest extends BaseBlockTypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Knp\Menu\Provider\MenuProviderInterface
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Knp\Menu\Matcher\MatcherInterface
     */
    protected $matcher;

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->provider = $this->getMock('Knp\Menu\Provider\MenuProviderInterface');
        $this->matcher = $this->getMock('Knp\Menu\Matcher\MatcherInterface');
        $layoutFactoryBuilder
            ->addType(new MenuType($this->provider, $this->matcher));
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testSetDefaultOptions($options, $expected)
    {
        $resolvedOptions = $this->resolveOptions(MenuType::NAME, $options);
        $this->assertEquals($expected, $resolvedOptions);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'required options' => [
                'options' => ['menu' => 'Top Menu'],
                'expected' => [
                    'depth' => null,
                    'matchingDepth' => null,
                    'currentAsLink' => true,
                    'currentClass' => 'current',
                    'ancestorClass' => 'current_ancestor',
                    'firstClass' => 'first',
                    'lastClass' => 'last',
                    'allow_safe_labels' => false,
                    'clear_matcher' => true,
                    'leaf_class' => null,
                    'branch_class' => null,
                    'menu' => 'Top Menu',
                    'child_attr' => [],
                    'link_attr' => [],
                    'label_attr' => [],
                ]
            ],
            'all options' => [
                'options' => [
                    'menu' => 'Top Menu',
                    'depth' => 5,
                    'matchingDepth' => 1,
                    'currentAsLink' => false,
                    'currentClass' => 'active',
                    'ancestorClass' => 'active',
                    'firstClass' => 'first-item',
                    'lastClass' => 'last-item',
                    'allow_safe_labels' => true,
                    'clear_matcher' => false,
                    'leaf_class' => 'leaf',
                    'branch_class' => 'branch',
                    'child_attr' => ['class' => 'child-class'],
                    'link_attr' => ['class' => 'link-class'],
                    'label_attr' => ['class' => 'label-class'],
                ],
                'expected' => [
                    'depth' => 5,
                    'matchingDepth' => 1,
                    'currentAsLink' => false,
                    'currentClass' => 'active',
                    'ancestorClass' => 'active',
                    'firstClass' => 'first-item',
                    'lastClass' => 'last-item',
                    'allow_safe_labels' => true,
                    'clear_matcher' => false,
                    'leaf_class' => 'leaf',
                    'branch_class' => 'branch',
                    'menu' => 'Top Menu',
                    'child_attr' => ['class' => 'child-class'],
                    'link_attr' => ['class' => 'link-class'],
                    'label_attr' => ['class' => 'label-class'],
                ]
            ],
        ];
    }

    public function testFinishView()
    {
        $type = $this->getBlockType(MenuType::NAME);
        $menuName = 'Footer Menu';
        $rootView = new BlockView();
        $view = new BlockView($rootView);
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $menu = $this->getMock('Knp\Menu\ItemInterface');
        $options = new Options([
            'menu' => $menuName,
            'child_attr' => [],
            'link_attr' => [],
            'label_attr' => [],
        ]);

        $this->provider->expects($this->once())
            ->method('has')
            ->with($menuName)
            ->willReturn(true);
        $this->provider->expects($this->once())
            ->method('get')
            ->with($menuName)
            ->willReturn($menu);

        $type->finishView($view, $block, $options);

        $this->assertEquals($menu, $view->vars['item']);
        $this->assertEquals($options, $view->vars['options']);
        $this->assertEquals($this->matcher, $view->vars['matcher']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Menu "FooMenu" doesn't exist.
     */
    public function testFinishViewWithException()
    {
        $type = $this->getBlockType(MenuType::NAME);
        $menuName = 'FooMenu';
        $rootView = new BlockView();
        $view = new BlockView($rootView);
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $this->provider->expects($this->once())
            ->method('has')
            ->with($menuName)
            ->willReturn(false);

        $type->finishView($view, $block, new Options(['menu' => $menuName]));
    }

    public function testGetName()
    {
        $type = $this->getBlockType(MenuType::NAME);

        $this->assertSame(MenuType::NAME, $type->getName());
    }
}
