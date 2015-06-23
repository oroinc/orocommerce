<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserRoleType extends AbstractType
{
    const NAME = 'orob2b_customer_account_user_role';

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
                    'label' => 'orob2b.customer.accountuserrole.role.label',
                    'required' => true,
                    'constraints' => [new Length(['min' => 3, 'max' => 32])]
                ]
            )
            ->add(
                'appendUsers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\CustomerBundle\Entity\AccountUser',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'removeUsers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\CustomerBundle\Entity\AccountUser',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            );

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => $this->dataClass]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
