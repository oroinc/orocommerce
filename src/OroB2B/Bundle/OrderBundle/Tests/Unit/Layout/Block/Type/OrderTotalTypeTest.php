<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

use OroB2B\Bundle\OrderBundle\Layout\Block\Type\OrderTotalType;

class OrderTotalTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required options "subtotals", "total" are missing.
     */
    public function testBuildViewWithoutData()
    {
        $this->getBlockView(OrderTotalType::NAME, []);
    }

    /** {@inheritdoc} */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $layoutFactoryBuilder->addType(new OrderTotalType());
    }

    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(OrderTotalType::NAME, ['total' => [], 'subtotals' => []]);

        $this->assertEquals([], $view->vars['total']);
        $this->assertEquals([], $view->vars['subtotals']);
    }

    public function testBuildView()
    {
        $total = ['value' => 1, 'label' => 'label'];
        $subtotals = ['shipping' => ['value' => 1, 'label' => 'label']];
        $view = $this->getBlockView(
            OrderTotalType::NAME,
            [
                'total' => $total,
                'subtotals' => $subtotals
            ]
        );

        $this->assertEquals($total, $view->vars['total']);
        $this->assertEquals($subtotals, $view->vars['subtotals']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(OrderTotalType::NAME);

        $this->assertSame(OrderTotalType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(OrderTotalType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
