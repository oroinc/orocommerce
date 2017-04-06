<?php

namespace Oro\Bundle\PayPalBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\PaymentBundle\Form\Type\CreditCardType as ParentCreditCardType;

class CreditCardType extends AbstractType
{
    const NAME = 'oro_paypal_credit_card';

    public function getParent()
    {
        return ParentCreditCardType::class;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'EXPDATE',
            'hidden',
            [
                'attr' => [
                    'data-gateway' => true,
                ],
            ]
        );

        if ($options['zeroAmountAuthorizationEnabled']) {
            $builder->add(
                'save_for_later',
                'checkbox',
                [
                    'required' => false,
                    'label' => 'oro.paypal.credit_card.save_for_later.label',
                    'mapped' => false,
                    'data' => true,
                    'attr' => [
                        'data-save-for-later' => true,
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'oro.paypal.methods.credit_card.label',
            'zeroAmountAuthorizationEnabled' => false,
        ]);
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
