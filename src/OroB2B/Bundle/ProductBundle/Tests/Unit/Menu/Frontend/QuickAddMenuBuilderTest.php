<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Menu\Frontend;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Menu\Frontend\QuickAddMenuBuilder;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;

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
        $this->componentRegistry = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry');

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
                    'orob2b.product.frontend.quick_add.title',
                    [
                        'route' => 'orob2b_product_frontend_quick_add',
                        'extras' => [
                            'position' => 500,
                            'description' => 'orob2b.product.frontend.quick_add.description',
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
