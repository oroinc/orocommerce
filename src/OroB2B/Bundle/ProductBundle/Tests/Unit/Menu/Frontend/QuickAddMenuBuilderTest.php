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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->componentRegistry = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry');

        $this->builder = new QuickAddMenuBuilder($this->componentRegistry, $this->translator);
    }

    protected function tearDown()
    {
        unset($this->builder, $this->componentRegistry, $this->translator);
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
            $this->translator->expects($this->at(0))
                ->method('trans')
                ->with('orob2b.product.frontend.quick_add.title')
                ->willReturn('Title');

            $this->translator->expects($this->at(1))
                ->method('trans')
                ->with('orob2b.product.frontend.quick_add.description')
                ->willReturn('Description');

            $menu->expects($this->once())
                ->method('addChild')
                ->with(
                    'Title',
                    [
                        'route' => 'orob2b_product_frontend_quick_add',
                        'extras' => [
                            'position' => 500,
                            'description' => 'Description',
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
