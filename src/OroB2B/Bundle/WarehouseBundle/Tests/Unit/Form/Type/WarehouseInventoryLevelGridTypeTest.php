<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;

class WarehouseInventoryLevelGridTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var WarehouseInventoryLevelGridType
     */
    protected $type;

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    protected function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->type = new WarehouseInventoryLevelGridType($this->formFactory);
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
            ->with('product');
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('product', 'OroB2B\Bundle\ProductBundle\Entity\Product');

        $this->type->configureOptions($resolver);
    }

    public function testFinishView()
    {
        $kgUnit = new ProductUnit();
        $kgUnit->setCode('kg')->setDefaultPrecision(3);
        $itemUnit = new ProductUnit();
        $itemUnit->setCode('item')->setDefaultPrecision(0);

        $kgPrecision = new ProductUnitPrecision();
        $kgPrecision->setUnit($kgUnit)->setPrecision(1);
        $itemPrecision = new ProductUnitPrecision();
        $itemPrecision->setUnit($itemUnit)->setPrecision(0);

        $productId = 42;
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        $product->addUnitPrecision($kgPrecision)->addUnitPrecision($itemPrecision);

        $constraints = ['some' => 'constraints'];
        $constraintsView = new FormView();
        $constraintsView->vars['attr']['data-validation'] = json_encode($constraints);

        $constraintsForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $constraintsForm->expects($this->once())
            ->method('createView')
            ->willReturn($constraintsView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with('number', null, $this->isType('array'))
            ->willReturn($constraintsForm);

        $view = new FormView();
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $options = ['product' => $product];

        $this->type->finishView($view, $form, $options);
        $this->assertArrayHasKey('product', $view->vars);
        $this->assertArrayHasKey('unitPrecisions', $view->vars);
        $this->assertArrayHasKey('quantityConstraints', $view->vars);
        $this->assertEquals($product, $view->vars['product']);
        $this->assertEquals(['kg' => 1, 'item' => 0], $view->vars['unitPrecisions']);
        $this->assertEquals($constraints, $view->vars['quantityConstraints']);
    }
}
