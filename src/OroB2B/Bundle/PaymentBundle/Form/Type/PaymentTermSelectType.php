<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class PaymentTermSelectType extends AbstractType
{
    const NAME = 'orob2b_payment_term_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_payment_term',
                'create_form_route' => 'orob2b_payment_term_create',
                'configs' => [
                    'placeholder' => 'orob2b.payment.paymentterm.form.choose'
                ]
            ]
        );
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
        return OroEntitySelectOrCreateInlineType::NAME;
    }
}
