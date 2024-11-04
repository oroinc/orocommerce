<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Menu\Frontend;

use Knp\Menu\ItemInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Menu\Frontend\QuickAddMenuBuilder;

class QuickAddMenuBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ComponentProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var QuickAddMenuBuilder */
    private $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ComponentProcessorRegistry::class);

        $this->builder = new QuickAddMenuBuilder($this->processorRegistry);
    }

    /**
     * @dataProvider getBuildDataProvider
     */
    public function testBuild(bool $hasAllowedProcessors)
    {
        $this->processorRegistry->expects(self::once())
            ->method('hasAllowedProcessors')
            ->willReturn($hasAllowedProcessors);

        $menu = $this->createMock(ItemInterface::class);

        if ($hasAllowedProcessors) {
            $menu->expects(self::once())
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
        } else {
            $menu->expects(self::never())
                ->method('addChild');
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
