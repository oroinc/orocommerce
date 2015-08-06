<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;

class OrderType extends AbstractType
{
    const NAME = 'orob2b_order_type';

    /** @var  string */
    protected $dataClass;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

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

        if ($this->isOverridePaymentTermGranted()) {
            $builder
                ->add(
                    'paymentTerm',
                    PaymentTermSelectType::NAME,
                    [
                        'label' => 'orob2b.order.payment_term.label',
                        'required' => false
                    ]
                );
        }
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

    /**
     * @return bool
     */
    protected function isOverridePaymentTermGranted()
    {
        return $this->securityFacade->isGranted('orob2b_order_payment_term_account_can_override');
    }
}
