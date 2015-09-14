<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeType;
use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

abstract class AbstractAccountUserRoleType extends AbstractType
{
    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'label',
                'text',
                [
                    'label' => 'orob2b.account.accountuserrole.role.label',
                    'required' => true,
                    'constraints' => [new Length(['min' => 3, 'max' => 32])]
                ]
            )
            ->add(
                'appendUsers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'removeUsers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            );

        foreach ($options['privilege_config'] as $fieldName => $config) {
            $builder->add(
                $fieldName,
                PrivilegeCollectionType::NAME,
                [
                    'type' => AclPrivilegeType::NAME,
                    'allow_add' => true,
                    'prototype' => false,
                    'allow_delete' => false,
                    'mapped' => false,
                    'options' => [
                        'privileges_config' => $config,
                    ],
                    'page_component_options' => [
                        'accessLevelRoute' => 'orob2b_account_acl_access_levels'
                    ],
                ]
            );
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var AccountUserRole|null $role */
            $role = $event->getData();
            // set role if it's not defined yet
            if ($role && !$role->getRole()) {
                $label = $role->getLabel();
                if ($label) {
                    $role->setRole($label);
                }
            }
        }, 10);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['privilege_config']);
        $resolver->setDefaults(['data_class' => $this->dataClass]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['privilegeConfig'] = $options['privilege_config'];
    }
}
