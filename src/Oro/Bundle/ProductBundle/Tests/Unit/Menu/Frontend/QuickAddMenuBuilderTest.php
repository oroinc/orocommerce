<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Menu\Frontend;

use Oro\Bundle\ProductBundle\Menu\Frontend\QuickAddMenuBuilder;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;

class QuickAddMenuBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuickAddMenuBuilder
     */
    protected $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorRegistry
     */
    protected $componentRegistry;

    protected function setUp()
    {
        $this->componentRegistry = $this
            ->getMock('Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry');

        $this->builder = new QuickAddMenuBuilder($this->componentRegistry);
    }

    protected function tearDown()
    {
        unset($this->builder, $this->componentRegistry);
    }

    /**
     * @param bool $hasAllowedProcessor
     *
     * @dataProvider getBuildDataProvider
     */
    public function testBuild($hasAllowedProcessor)
    {
        $this->componentRegistry->expects($this->once())
            ->method('hasAllowedProcessor')->willReturn($hasAllowedProcessor);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Knp\Menu\ItemInterface $menu */
        $menu = $this->getMock('Knp\Menu\ItemInterface');

        if ($hasAllowedProcessor) {
            $menu->expects($this->once())
                ->method('addChild')
                ->with(
                    'oro.product.frontend.quick_add.title',
                    [
                        'route' => 'oro_product_frontend_quick_add',
                        'extras' => [
                            'position' => 500,
                            'description' => 'oro.product.frontend.quick_add.description',
                        ],
                    ]
                );
        }

        $this->builder->build($menu);
    }

    /**
     * @return array
     */
    public function getBuildDataProvider()
    {
        return [
            'build' => [true],
            'fail' => [false],
        ];
    }
}
