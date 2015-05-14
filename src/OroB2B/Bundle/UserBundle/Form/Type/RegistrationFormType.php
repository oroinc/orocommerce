<?php

namespace OroB2B\Bundle\UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

use Symfony\Component\Form\FormBuilderInterface;

class RegistrationFormType extends BaseType
{
    const NAME = 'orob2b_user_registration';

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstName',
                'text',
                [
                    'label' => 'orob2b_user.form.first_name.label',
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'label' => 'orob2b_user.form.last_name.label',
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'label' => 'orob2b_user.form.email.label',
                ]
            )
            ->add(
                'plainPassword',
                'repeated',
                [
                    'type' => 'password',
                    'first_options' => [
                        'label' => 'orob2b_user.form.password.label'
                    ],
                    'second_options' => [
                        'label' => 'orob2b_user.form.password_confirmation.label'
                    ],
                    'invalid_message' => 'orob2b_user.message.password_mismatch',
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
