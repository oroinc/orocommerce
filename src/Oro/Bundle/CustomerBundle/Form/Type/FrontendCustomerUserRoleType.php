<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;

class FrontendCustomerUserRoleType extends AbstractCustomerUserRoleType
{
    const NAME = 'oro_customer_frontend_customer_user_role';

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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'updateCustomerUsers']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * PRE_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $event->getForm()->add('customer', FrontendOwnerSelectType::NAME, [
            'label' => 'oro.customer.customer.entity_label',
            'targetObject' => $event->getData()
        ]);
    }

    /**
     * @param FormEvent $event
     */
    public function updateCustomerUsers(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();

        $predefinedRole = $options['predefined_role'];
        if (!$predefinedRole instanceof CustomerUserRole) {
            return;
        }

        $role = $event->getData();
        if (!$role instanceof CustomerUserRole || !$role->getCustomer()) {
            return;
        }

        $customerUsers = $predefinedRole->getCustomerUsers()->filter(
            function (CustomerUser $customerUser) use ($role) {
                return $customerUser->getCustomer() &&
                    $customerUser->getCustomer()->getId() === $role->getCustomer()->getId();
            }
        );

        $customerUsers->map(
            function (CustomerUser $customerUser) use ($predefinedRole) {
                $customerUser->removeRole($predefinedRole);
            }
        );

        $event->getForm()->get('appendUsers')->setData($customerUsers->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'access_level_route' => 'oro_customer_frontend_acl_access_levels',
                'predefined_role' => null,
                'hide_self_managed' => true
            ]
        );
    }
}
