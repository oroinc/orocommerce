<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

use OroB2B\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductFormExtension */
    protected $extension;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->repo = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BShippingBundle:ProductShippingOptions')
            ->willReturn($this->repo);

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BShippingBundle:ProductShippingOptions')
            ->willReturn($this->manager);

        $this->extension = new ProductFormExtension($this->registry);
    }

    /**
     * @param mixed $product
     *
     * @dataProvider formDataProvider
     */
    public function testBuildForm($product)
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                ProductFormExtension::FORM_ELEMENT_NAME,
                ProductShippingOptionsCollectionType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.entity_plural_label',
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [new UniqueProductUnitShippingOptions()],
                    'options' => [
                        'product' => $product,
                    ],
                ]
            );

        $builder->expects($this->exactly(3))->method('addEventListener');
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder->expects($this->at(3))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit']);
        $builder->expects($this->at(4))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $builder->expects($this->once())->method('getData')->willReturn($product);

        $this->extension->buildForm($builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->extension->getExtendedType(), ProductType::NAME);
    }

    /**
     * @dataProvider formDataProvider
     *
     * @param null|Product $product
     */
    public function testOnPostSetData($product)
    {
        $event = $this->createEvent($product);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $form->expects($product ? $this->once() : $this->never())->method('setData');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitEmptyProduct()
    {
        $event = $this->createEvent();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $form->expects($this->never())->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitValidData()
    {
        $product = $this->createMockProduct();

        $event = $this->createEvent($this->createMockProduct());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $event->getForm()->expects($this->once())->method('isValid')->willReturn(true);

        $removedOption = $this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => 42]);

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['product' => $product], ['productUnit' => 'ASC'])
            ->willReturn([$removedOption]);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    $this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => 1]),
                    $this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => null]),
                ]
            );

        $this->manager->expects($this->exactly(2))->method('persist');
        $this->manager->expects($this->once())->method('remove')->with($removedOption);

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidData()
    {
        $event = $this->createEvent($this->createMockProduct());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $event->getForm()->expects($this->once())->method('isValid')->willReturn(false);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => 1])]);

        $this->manager->expects($this->never())->method('persist');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPreSubmit()
    {
        $product = $this->createMockProduct();

        $event = $this->createEvent(
            [
                ProductFormExtension::FORM_ELEMENT_NAME => [
                    5 => ['productUnit' => 'test2'],
                    10 => ['productUnit' => 'test1']
                ]
            ]
        );
        $event->expects($this->once())
            ->method('setData')
            ->with(
                [
                    ProductFormExtension::FORM_ELEMENT_NAME => [
                        0 => ['productUnit' => 'test1'],
                        5 => ['productUnit' => 'test2']
                    ]
                ]
            );

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())->method('getData')->willReturn($product);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $childForm */
        $childForm = $form->get(ProductFormExtension::FORM_ELEMENT_NAME);
        $childForm->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    $this->getEntity(
                        'OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions',
                        [
                            'id' => 42,
                            'productUnit' => $this->getEntity(
                                'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'test1']
                            )
                        ]
                    )
                ]
            );

        $this->extension->onPreSubmit($event);
    }

    public function testOnPreSubmitWithoutProduct()
    {
        $product = $this->createMockProduct(null);

        $event = $this->createEvent();
        $event->expects($this->never())->method('setData');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())->method('getData')->willReturn($product);

        $this->extension->onPreSubmit($event);
    }

    /**
     * @param object|null $data
     *
     * @return FormEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEvent($data = null)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with(ProductFormExtension::FORM_ELEMENT_NAME)
            ->willReturn($form);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->any())->method('getForm')->willReturn($mainForm);
        $event->expects($this->any())->method('getData')->willReturn($data);

        return $event;
    }

    /**
     * @return array
     */
    public function formDataProvider()
    {
        $productMock = $this->createMockProduct();

        return [
            'no product' => ['product' => null],
            'with product' => ['product' => $productMock],
        ];
    }

    /**
     * @param int $id
     * @return Product
     */
    private function createMockProduct($id = 1)
    {
        return $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $id]);
    }
}
