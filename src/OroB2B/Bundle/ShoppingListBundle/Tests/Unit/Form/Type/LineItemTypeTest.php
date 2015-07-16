<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\LineItemType;

class LineItemTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /**
     * @var LineItemType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LineItem
     */
    protected $lineItem;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormEvent
     */
    protected $formEvent;

    protected function setUp()
    {
        parent::setUp();

        $managerRegistry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new LineItemType($managerRegistry, $roundingService);
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setProductClass(self::PRODUCT_CLASS);
        $this->lineItem = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem');
        $this->formEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItem->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));

        $builder->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->lineItem));

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'product',
                ProductSelectType::NAME,
                [
                    'required'       => true,
                    'label'          => 'orob2b.pricing.productprice.product.label',
                    'create_enabled' => false,
                    'disabled'       => false,
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'quantity',
                'text',
                [
                    'required' => true,
                    'label'    => 'orob2b.pricing.productprice.quantity.label'
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'unit',
                ProductUnitSelectionType::NAME,
                [
                    'required'    => true,
                    'label'       => 'orob2b.pricing.productprice.unit.label',
                    'empty_data'  => null,
                    'empty_value' => 'orob2b.pricing.productprice.unit.choose'
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'notes',
                'textarea',
                [
                    'required'   => false,
                    'label'      => 'orob2b.shoppinglist.lineitem.notes.label',
                    'empty_data' => null,
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(5))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA);

        $builder->expects($this->at(6))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT);

        $this->type->buildForm($builder, []);
    }

    public function testPreSetDataNewLineItem()
    {
        $this->lineItem->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));

        $this->formEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->lineItem));

        $this->formEvent->expects($this->never())
            ->method('getForm');

        $this->type->preSetData($this->formEvent);
    }

    public function testPreSetDataExistingLineItem()
    {
        $this->lineItem->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->once())
            ->method('add')
            ->with(
                'unit',
                ProductUnitSelectionType::NAME,
                [
                    'required'      => true,
                    'label'         => 'orob2b.pricing.productprice.unit.label',
                    'empty_data'    => null,
                    'empty_value'   => 'orob2b.pricing.productprice.unit.choose',
                    'query_builder' => function () {
                    }
                ]
            );

        $this->formEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->lineItem));

        $this->formEvent->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->type->preSetData($this->formEvent);
    }

    public function testPreSubmitDataNoData()
    {
        $this->formEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([]));

        $this->formEvent->expects($this->never())
            ->method('setData');

        $this->type->preSubmitData($this->formEvent);
    }

    public function testPreSubmitData()
    {
        $data = [
            'product'  => 1,
            'unit'     => 1,
            'quantity' => 1,
        ];

        $this->formEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $unitPrecision = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision');
        $unitPrecision->expects($this->once())
            ->method('getPrecision')
            ->will($this->returnValue(1));

        $product = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product');
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($data['unit'])
            ->will($this->returnValue($unitPrecision));

        $objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $objectRepository->expects($this->once())
            ->method('find')
            ->with($data['product'])
            ->will($this->returnValue($product));

        $managerRegistry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $managerRegistry->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($objectRepository));

        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formEvent->expects($this->once())
            ->method('setData');

        $type = new LineItemType($managerRegistry, $roundingService);
        $type->preSubmitData($this->formEvent);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'        => self::DATA_CLASS,
                    'validation_groups' => function () {
                    }
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(LineItemType::NAME, $this->type->getName());
    }
}
