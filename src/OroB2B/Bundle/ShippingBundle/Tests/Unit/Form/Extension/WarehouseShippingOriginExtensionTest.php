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

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\ShippingBundle\Form\Extension\WarehouseShippingOriginExtension;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingOriginWarehouseType;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WarehouseShippingOriginExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingOriginProvider;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var WarehouseShippingOriginExtension */
    protected $extension;

    protected function setUp()
    {
        $this->shippingOriginProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2BShippingBundle:ShippingOriginWarehouse')
            ->willReturn($this->manager);

        $this->extension = new WarehouseShippingOriginExtension($this->shippingOriginProvider, $this->registry);
    }

    protected function tearDown()
    {
        unset($this->extension, $this->shippingOriginProvider, $this->registry, $this->manager);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(WarehouseType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'shipping_origin_warehouse',
                ShippingOriginWarehouseType::NAME,
                [
                    'mapped' => false,
                    'label' => 'orob2b.shipping.warehouse.section.shipping_origin'
                ]
            );
        $builder->expects($this->exactly(2))->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit']);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSetDataWithoutWarehouse()
    {
        $this->shippingOriginProvider->expects($this->never())->method($this->anything());

        $this->extension->onPostSetData($this->createEvent());
    }

    public function testOnPostSetData()
    {
        $warehouse = $this->getEntity('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse');
        $shippingOrigin = new ShippingOrigin();

        $this->shippingOriginProvider->expects($this->once())
            ->method('getShippingOriginByWarehouse')
            ->with($warehouse)
            ->willReturn($shippingOrigin);

        $event = $this->createEvent($warehouse);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm()->get('shipping_origin_warehouse');
        $form->expects($this->once())->method('setData')->with($shippingOrigin);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitWithoutWarehouse()
    {
        $event = $this->createEvent();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm();
        $form->expects($this->never())->method('isValid');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->getEntity('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse'));

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())->method('isValid')->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $shippingOriginForm */
        $shippingOriginForm = $form->get('shipping_origin_warehouse');
        $shippingOriginForm->expects($this->never())->method('getData');

        $this->extension->onPostSubmit($event);
    }

    /**
     * @dataProvider onPostSubmitDataProvider
     *
     * @param Warehouse $warehouse
     * @param ShippingOriginWarehouse $shippingOriginWarehouse
     */
    public function testOnPostSubmitNewWarehouseAndUseSystem(
        Warehouse $warehouse,
        ShippingOriginWarehouse $shippingOriginWarehouse = null
    ) {
        $shippingOrigin = new ShippingOrigin();
        $shippingOrigin->setSystem(true);

        $event = $this->createEvent($warehouse);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())->method('isValid')->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $shippingOriginForm */
        $shippingOriginForm = $form->get('shipping_origin_warehouse');
        $shippingOriginForm->expects($this->once())->method('getData')->willReturn($shippingOrigin);

        if (!$shippingOriginWarehouse) {
            $this->assertRegistryCalled(false);
            $this->manager->expects($this->never())->method($this->anything());
        } else {
            $repository = $this->assertRegistryCalled();
            $repository->expects($this->once())
                ->method('findOneBy')
                ->with(['warehouse' => $warehouse])
                ->willReturn($shippingOriginWarehouse);

            $this->manager->expects($this->once())->method('remove')->with($shippingOriginWarehouse);
        }

        $this->extension->onPostSubmit($event);
    }

    /**
     * @dataProvider onPostSubmitDataProvider
     *
     * @param Warehouse $warehouse
     * @param ShippingOriginWarehouse $shippingOriginWarehouse
     */
    public function testOnPostSubmitNoUseSystem(
        Warehouse $warehouse,
        ShippingOriginWarehouse $shippingOriginWarehouse = null
    ) {
        $shippingOrigin = new ShippingOrigin([
            'country' => 'test country',
            'region' => 'test region',
            'region_text' => 'test region_text',
            'postalCode' => 'test postalCode',
            'city' => 'test city',
            'street' => 'test street',
            'street2' => 'test street2'
        ]);
        $shippingOrigin->setSystem(false);

        $event = $this->createEvent($warehouse);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $event->getForm();
        $form->expects($this->once())->method('isValid')->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $shippingOriginForm */
        $shippingOriginForm = $form->get('shipping_origin_warehouse');
        $shippingOriginForm->expects($this->once())
            ->method('getData')
            ->willReturn($shippingOrigin);

        if (!$shippingOriginWarehouse) {
            $this->assertRegistryCalled(false);

            $this->manager->expects($this->once())
                ->method('persist')
                ->with($this->isInstanceOf('OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse'))
                ->willReturnCallback(function (ShippingOriginWarehouse $entity) use (&$shippingOriginWarehouse) {
                    $shippingOriginWarehouse = $entity;
                });
        } else {
            $repository = $this->assertRegistryCalled(true);
            $repository->expects($this->once())
                ->method('findOneBy')
                ->with(['warehouse' => $warehouse])
                ->willReturn($shippingOriginWarehouse);

            $this->manager->expects($this->never())->method('persist');
        }

        $this->extension->onPostSubmit($event);

        $this->assertEquals($shippingOrigin->getCountry(), $shippingOriginWarehouse->getCountry());
        $this->assertEquals($shippingOrigin->getRegion(), $shippingOriginWarehouse->getRegion());
        $this->assertEquals($shippingOrigin->getRegionText(), $shippingOriginWarehouse->getRegionText());
        $this->assertEquals($shippingOrigin->getPostalCode(), $shippingOriginWarehouse->getPostalCode());
        $this->assertEquals($shippingOrigin->getCity(), $shippingOriginWarehouse->getCity());
        $this->assertEquals($shippingOrigin->getStreet(), $shippingOriginWarehouse->getStreet());
        $this->assertEquals($shippingOrigin->getStreet2(), $shippingOriginWarehouse->getStreet2());
    }

    /**
     * @return array
     */
    public function onPostSubmitDataProvider()
    {
        return [
            [
                'warehouse' => $this->getEntity('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse')
            ],
            [
                'warehouse' => $this->getEntity('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse', ['id' => 42]),
                'shippingOriginWarehouse' => new ShippingOriginWarehouse()
            ]
        ];
    }

    /**
     * @param object|null $data
     * @return FormEvent
     */
    protected function createEvent($data = null)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())->method('get')->with('shipping_origin_warehouse')->willReturn($form);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param bool $expects
     * @return ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertRegistryCalled($expects = true)
    {
        /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->manager->expects($expects ? $this->once() : $this->never())
            ->method('getRepository')
            ->with('OroB2BShippingBundle:ShippingOriginWarehouse')
            ->willReturn($repository);

        return $repository;
    }
}
