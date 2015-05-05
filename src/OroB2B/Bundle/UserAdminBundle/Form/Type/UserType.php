<?php

namespace OroB2B\Bundle\UserAdminBundle\Form\Type;

use OroB2B\Bundle\UserAdminBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class UserType extends AbstractType
{
    const NAME = 'orob2b_user_admin_user';

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstName',
                'text',
                ['required' => true, 'label' => 'orob2b.useradmin.user.first_name.label']
            )
            ->add('lastName', 'text', ['required' => true, 'label' => 'orob2b.useradmin.user.last_name.label'])
            ->add('email', 'email', ['required' => true, 'label' => 'orob2b.useradmin.user.email.label'])

            ->add('enabled', 'checkbox', ['required' => false, 'label' => 'orob2b.useradmin.user.enabled.label'])
            ->add(
                'groups',
                'entity',
                [
                    'label'     => null,
                    'class'     => 'OroB2BUserAdminBundle:Group',
                    'property'  => 'name',
                    'multiple'  => true,
                    'expanded'  => true,
                    'required'  => false,
                ]
            )
        ;
        $data = $builder->getData();

        $passwordOptions = [
            'type'            => 'password',
            'required'        => false,
            'first_options'   => ['label' => 'orob2b.useradmin.user.password.label'],
            'second_options'  => ['label' => 'orob2b.useradmin.user.password_confirmation.label'],
            'invalid_message' => 'orob2b.useradmin.message.password_mismatch'
        ];

        if ($data instanceof User && $data->getId()) {
            $passwordOptions = array_merge($passwordOptions, ['required' => false, 'validation_groups' => false]);
        } else {
            $passwordOptions = array_merge($passwordOptions, ['required' => true, 'validation_groups' => 'create']);
        }

        $builder->add(
            'plainPassword',
            'repeated',
            $passwordOptions
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'           => 'OroB2B\Bundle\UserAdminBundle\Entity\User',
            'intention'            => 'user',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
