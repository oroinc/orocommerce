<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingOriginWarehouseType extends AbstractShippingOriginType
{
    const NAME = 'orob2b_shipping_origin_warehouse';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('system', 'checkbox', ['label' => 'orob2b.shipping.warehouse.use_system_configuration']);

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass
            ]
        );
    }

    public function getName()
    {
        return self::NAME;
    }
}
