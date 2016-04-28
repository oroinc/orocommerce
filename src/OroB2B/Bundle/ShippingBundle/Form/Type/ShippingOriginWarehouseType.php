<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingOriginWarehouseType extends AbstractType
{
    const NAME = 'orob2b_shipping_origin_warehouse';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('system', 'checkbox', ['label' => 'orob2b.shipping.warehouse.use_system_configuration']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ShippingOriginType::NAME;
    }
}
