<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType;

class OrderType extends AbstractType
{
    const NAME = 'orob2b_order_type';

    /** @var  string */
    protected $dataClass;

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // @todo: user selector
            ->add('accountUser', 'entity', ['class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser'])
            ->add(
                'billingAddress',
                OrderAddressType::NAME,
                [
                    'label' => 'orob2b.order.billing_address.label',
                    'order' => $options['data'],
                    'required' => false,
                    'addressType' => AddressType::TYPE_BILLING,
                ]
            )
            ->add(
                'shippingAddress',
                OrderAddressType::NAME,
                [
                    'label' => 'orob2b.order.shipping_address.label',
                    'order' => $options['data'],
                    'required' => false,
                    'addressType' => AddressType::TYPE_SHIPPING,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
            ]
        );
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
