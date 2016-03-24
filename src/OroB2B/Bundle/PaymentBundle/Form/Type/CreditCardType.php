<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CreditCardType extends AbstractType
{
    const NAME = 'orob2b_payment_credit_card';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'ACCT',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.card_number.label',
                    'mapped' => false
                ]
            )
            ->add(
                'expirationDate',
                'orob2b_payment_credit_card_expiration_date',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.expiration_date.label',
                    'mapped' => false,
                    'placeholder' => [
                        'year' => 'Year',
                        'month' => 'Month'
                    ]
                ]
            )
            ->add(
                'EXPDATE',
                'hidden'
            )
            ->add(
                'CVV2',
                'password',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.cvv2.label',
                    'always_empty' => true,
                    'mapped' => false,
                    'block_name' => 'payment_credit_card_cvv'
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($view->children as $child) {
            $child->vars['full_name'] = $child->vars['name'];
        }
    }


    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
