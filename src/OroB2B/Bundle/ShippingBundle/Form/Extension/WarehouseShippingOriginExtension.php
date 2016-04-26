<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Form\Type\WarehouseType;

class WarehouseShippingOriginExtension extends AbstractTypeExtension
{
    /**
     * @var ShippingOriginProvider
     */
    private $shippingOriginProvider;

    /**
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function __construct(ShippingOriginProvider $shippingOriginProvider)
    {
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'shipping_origin_warehouse',
            'orob2b_shipping_origin_warehouse',
            [
                'required' => false,
                'label' => 'orob2b.tax.system_configuration.fields.use_as_base.shipping_origin.label'
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function postSubmit(FormEvent $formEvent)
    {
        //todo impl
    }

    /**
     * {@inheritdoc}
     */
    public function preSetData(FormEvent $formEvent)
    {
        $entity = $formEvent->getData();

        $form = $formEvent->getForm();

        if (!$entity instanceof Warehouse) {
            return;
        }

        $shippingOrigin = $this->shippingOriginProvider->getShippingOriginByWarehouse($entity);

        if ($shippingOrigin instanceof ShippingOriginWarehouse) {
            $form->get('shipping_origin_warehouse')->setData($shippingOrigin);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return WarehouseType::NAME;
    }
}
