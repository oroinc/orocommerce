<?php

namespace OroB2B\Bundle\UserAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

class GroupType extends AbstractType
{
    const NAME = 'orob2b_user_admin_group';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'label' => 'orob2b.useradmin.group.name.label',
                    'required' => true
                ]
            )
            ->add(
                'roles',
                FrontendRolesType::NAME,
                [
                    'label' => ' ',
                    'required' => false,
                ]
            )
            ->add(
                'appendUsers',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BUserAdminBundle:User',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeUsers',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BUserAdminBundle:User',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'OroB2B\Bundle\UserAdminBundle\Entity\Group',
                'intention'  => 'group',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
