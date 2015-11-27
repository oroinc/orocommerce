<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;

use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;

class WarehouseInventoryLevelGridTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseInventoryLevelGridType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new WarehouseInventoryLevelGridType();
    }

    public function testGetName()
    {
        $this->assertEquals(WarehouseInventoryLevelGridType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(DataChangesetType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['product_id']);

        $this->type->configureOptions($resolver);
    }

    public function testFinishView()
    {
        $productId = 42;
        $view = new FormView();
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $options = ['product_id' => $productId];

        $this->type->finishView($view, $form, $options);
        $this->assertArrayHasKey('product_id', $view->vars);
        $this->assertEquals($productId, $view->vars['product_id']);
    }
}
