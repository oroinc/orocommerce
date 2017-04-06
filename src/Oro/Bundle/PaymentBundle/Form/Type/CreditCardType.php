<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;

class CreditCardType extends AbstractType
{
    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'ACCT',
            'text',
            [
                'required' => true,
                'label' => 'oro.payment.credit_card.card_number.label',
                'mapped' => false,
                'attr' => [
                    'data-validation' => [
                        'credit-card-number' => [
                            'message' => 'oro.payment.validation.credit_card',
                            'payload' => null,
                        ],
                        'credit-card-type' => [
                            'message' => 'oro.payment.validation.credit_card_type',
                            'payload' => null,
                        ]
                    ],
                    'data-credit-card-type-validator' => 'credit-card-type',
                    'data-card-number' => true,
                    'autocomplete' => 'off',
                    'data-gateway' => true,
                    'placeholder' => false,
                ],
                'constraints' => [
                    new Integer(),
                    new NotBlank(),
                    new Length(['min' => '12', 'max' => '19']),
                ],
            ]
        )->add(
            'expirationDate',
            'oro_payment_credit_card_expiration_date',
            [
                'required' => true,
                'label' => 'oro.payment.credit_card.expiration_date.label',
                'mapped' => false,
                'placeholder' => [
                    'year' => 'Year',
                    'month' => 'Month',
                ],
                'attr' => [
                    'data-expiration-date' => true,
                ],
            ]
        );

        if ($options['requireCvvEntryEnabled']) {
            $builder->add(
                'CVV2',
                'password',
                [
                    'required' => true,
                    'label' => 'oro.payment.credit_card.cvv2.label',
                    'mapped' => false,
                    'block_name' => 'payment_credit_card_cvv',
                    'constraints' => [
                        new Integer(['message' => 'oro.payment.number.error']),
                        new NotBlank(),
                        new Length(['min' => 3, 'max' => 4]),
                    ],
                    'attr' => [
                        'data-card-cvv' => true,
                        'data-gateway' => true,
                        'placeholder' => false,
                    ],
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'requireCvvEntryEnabled' => true,
        ]);
    }
}
