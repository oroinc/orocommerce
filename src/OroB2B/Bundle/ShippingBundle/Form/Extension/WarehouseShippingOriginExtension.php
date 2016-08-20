<?php

namespace Oro\Bundle\ShippingBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginWarehouseType;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\Form\Type\WarehouseType;

class WarehouseShippingOriginExtension extends AbstractTypeExtension
{
    /** @var ShippingOriginProvider */
    protected $shippingOriginProvider;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ShippingOriginProvider $shippingOriginProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(ShippingOriginProvider $shippingOriginProvider, ManagerRegistry $registry)
    {
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return WarehouseType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'shipping_origin_warehouse',
                ShippingOriginWarehouseType::class,
                [
                    'mapped' => false,
                    'label' => 'oro.shipping.warehouse.section.shipping_origin'
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function onPostSetData(FormEvent $formEvent)
    {
        $data = $formEvent->getData();
        if (!$data instanceof Warehouse) {
            return;
        }

        $shippingOrigin = $this->shippingOriginProvider->getShippingOriginByWarehouse($data);

        $formEvent->getForm()->get('shipping_origin_warehouse')->setData($shippingOrigin);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function onPostSubmit(FormEvent $formEvent)
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = $formEvent->getData();
        if (!$warehouse) {
            return;
        }

        $form = $formEvent->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var ShippingOrigin $shippingOrigin */
        $shippingOrigin = $form->get('shipping_origin_warehouse')->getData();
        $shippingOriginWarehouse = $this->getShippingOriginWarehouse($warehouse);

        if ($shippingOrigin->isSystem()) {
            if ($shippingOriginWarehouse) {
                $this->getShippingOriginWarehouseManager()->remove($shippingOriginWarehouse);
            }
        } else {
            if (!$shippingOriginWarehouse) {
                $shippingOriginWarehouse = $this->createShippingOriginWarehouse($warehouse);
            }

            $shippingOriginWarehouse->import($shippingOrigin);
        }
    }

    /**
     * @param Warehouse $warehouse
     * @return ShippingOriginWarehouse|null
     */
    protected function getShippingOriginWarehouse(Warehouse $warehouse)
    {
        if (!$warehouse->getId()) {
            return null;
        }

        return $this->getShippingOriginWarehouseRepository()->findOneBy(['warehouse' => $warehouse]);
    }

    /**
     * @param Warehouse $warehouse
     * @return ShippingOriginWarehouse
     */
    protected function createShippingOriginWarehouse(Warehouse $warehouse)
    {
        $manager = $this->getShippingOriginWarehouseManager();

        $shippingOriginWarehouse = new ShippingOriginWarehouse();
        $shippingOriginWarehouse->setWarehouse($warehouse);

        $manager->persist($shippingOriginWarehouse);

        return $shippingOriginWarehouse;
    }

    /**
     * @return ObjectManager
     */
    protected function getShippingOriginWarehouseManager()
    {
        return $this->registry->getManagerForClass('OroShippingBundle:ShippingOriginWarehouse');
    }

    /**
     * @return ObjectRepository
     */
    protected function getShippingOriginWarehouseRepository()
    {
        return $this->getShippingOriginWarehouseManager()
            ->getRepository('OroShippingBundle:ShippingOriginWarehouse');
    }
}
