<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Validator\Constraints\UniqueCustomerUserNameAndEmail;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Represents late registration form type for guests checkout
 */
class LateRegistrationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('is_late_registration_enabled', CheckboxType::class, [
            'data' => true,
            'label' => 'oro.checkout.late_registration.label',
            'translation_domain' => 'messages'
        ]);

        $builder->add('email', EmailType::class, [
            'constraints' => [
                new NotBlank(),
                new Email(),
                new UniqueCustomerUserNameAndEmail()
            ],
            'label' => 'oro.customer.customeruser.email.label',
            'attr' => [
                'placeholder' => 'oro.customer.customeruser.placeholder.email'
            ],
            'translation_domain' => 'messages',
            'required' => true
        ]);

        $builder->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options' => [
                'label' => 'oro.customer.customeruser.password.label',
                'attr' => [
                    'placeholder' => 'oro.customer.customeruser.placeholder.password'
                ],
                'translation_domain' => 'messages'
            ],
            'second_options' => [
                'label' => 'oro.customer.customeruser.password_confirmation.label',
                'attr' => [
                    'placeholder' => 'oro.customer.customeruser.placeholder.password_confirmation'
                ],
                'translation_domain' => 'messages'
            ],
            'invalid_message' => 'oro.customer.message.password_mismatch',
            'constraints' => [
                new NotBlank(),
                new PasswordComplexity(),
            ],
            'required' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $lateRegistration = is_array($data)
                    && array_key_exists('is_late_registration_enabled', $data)
                    && $data['is_late_registration_enabled'];
                if (!$lateRegistration && $form->isSubmitted()) {
                    return [];
                } else {
                    return ['Default'];
                }
            }
        ]);
    }
}
