<?php

namespace OroB2B\Bundle\UserBundle\Form\Type;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;

use Symfony\Component\Form\FormBuilderInterface;

class ProfileFormType extends BaseType
{
    const NAME = 'orob2b_user_profile';

    /**
     * {@inheritDoc}
     */
    protected function buildUserForm(FormBuilderInterface $builder, array $options)
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
