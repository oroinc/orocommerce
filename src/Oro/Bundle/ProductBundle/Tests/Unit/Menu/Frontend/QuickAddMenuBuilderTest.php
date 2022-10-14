<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Menu\Frontend;

use Knp\Menu\ItemInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Menu\Frontend\QuickAddMenuBuilder;

class QuickAddMenuBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ComponentProcessorRegistry */
    private $componentRegistry;

    /** @var QuickAddMenuBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->componentRegistry = $this->createMock(ComponentProcessorRegistry::class);

        $this->builder = new QuickAddMenuBuilder($this->componentRegistry);
    }

    /**
     * @dataProvider getBuildDataProvider
     */
    public function testBuild(bool $hasAllowedProcessor)
    {
        $this->componentRegistry->expects($this->once())
            ->method('hasAllowedProcessor')
            ->willReturn($hasAllowedProcessor);

        $menu = $this->createMock(ItemInterface::class);

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

    public function getBuildDataProvider(): array
    {
        return [
            'build' => [true],
            'fail' => [false],
        ];
    }
}
