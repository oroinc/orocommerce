<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

use OroB2B\Bundle\ShippingBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;

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
        $this->repo = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo->expects($this->any())->method('getShippingOptionsByProduct')->willReturn([]);

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
        /** @var \PHPUnit_Framework_MockObject_MockObject $builder */
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

        $builder->expects($this->once())
            ->method('getData')
            ->willReturn($product);

        $this->extension->buildForm($builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals($this->extension->getExtendedType(), ProductType::NAME);
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testOnPostSetData($product)
    {
        $event = $this->createEvent($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $form->expects($product ? $this->once() : $this->never())->method('setData');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitEmptyProduct()
    {
        $event = $this->createEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $form->expects($this->never())->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitValidData()
    {
        $event = $this->createEvent($this->createMockProduct());
        /** @var \PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $event->getForm()->expects($this->once())->method('isValid')->willReturn(true);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn(
                [
                    $this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => 1]),
                    $this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => null]),
                ]
            );

        $this->manager->expects($this->atLeastOnce())->method('persist');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInValidData()
    {
        $event = $this->createEvent($this->createMockProduct());
        /** @var \PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get(ProductFormExtension::FORM_ELEMENT_NAME);

        $event->getForm()->expects($this->once())->method('isValid')->willReturn(false);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions', ['id' => 1])]);

        $this->manager->expects($this->never())->method('persist');

        $this->extension->onPostSubmit($event);
    }

    /**
     * @param object|null $data
     *
     * @return FormEvent
     */
    protected function createEvent($data = null)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with(ProductFormExtension::FORM_ELEMENT_NAME)
            ->willReturn($form);

        return new FormEvent($mainForm, $data);
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
     * @return object
     */
    private function createMockProduct()
    {
        return $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1]);
    }
}
