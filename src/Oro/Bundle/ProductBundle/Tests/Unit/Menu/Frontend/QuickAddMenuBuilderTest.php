<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Menu\Frontend;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Menu\Frontend\QuickAddMenuBuilder;

class QuickAddMenuBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuickAddMenuBuilder
     */
    protected $builder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ComponentProcessorRegistry
     */
    protected $componentRegistry;

    protected function setUp(): void
    {
        $this->componentRegistry = $this
            ->createMock('Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry');

        $this->builder = new QuickAddMenuBuilder($this->componentRegistry);
    }

    protected function tearDown(): void
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

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Knp\Menu\ItemInterface $menu */
        $menu = $this->createMock('Knp\Menu\ItemInterface');

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
