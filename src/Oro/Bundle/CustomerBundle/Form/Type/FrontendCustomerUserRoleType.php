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
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
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
        $role = $this->getRole($event);
        $predefinedRole = $this->getPredefinedRole($event);
        if (!$role || !$predefinedRole) {
            return;
        }

        $customerUsers = $predefinedRole->getCustomerUsers()->filter(
            function (CustomerUser $accountUser) use ($role) {
                return $accountUser->getCustomer() &&
                    $accountUser->getCustomer()->getId() === $role->getCustomer()->getId();
            }
        );

        $event->getForm()->get('appendUsers')->setData($customerUsers->toArray());
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $role = $this->getRole($event);
        $predefinedRole = $this->getPredefinedRole($event);

        if (!$role || !$predefinedRole) {
            return;
        }

        $form = $event->getForm();

        /** @var \SplObjectStorage|CustomerUser[] $addedUsers */
        $addedUsers = new \SplObjectStorage();
        foreach ($form->get('appendUsers')->getData() as $accountUser) {
            $addedUsers->attach($accountUser);
        }

        foreach ($form->get('removeUsers')->getData() as $accountUser) {
            $addedUsers->detach($accountUser);
        }

        foreach ($addedUsers as $accountUser) {
            $predefinedRole->removeCustomerUser($accountUser);
            $accountUser->removeRole($predefinedRole);
        }
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

    /**
     * @param FormEvent $event
     * @return null|CustomerUserRole
     */
    protected function getPredefinedRole(FormEvent $event)
    {
        $config = $event->getForm()->getConfig();
        $predefinedRole = $config->getOption('predefined_role');

        return ($predefinedRole !== null && $predefinedRole instanceof CustomerUserRole) ? $predefinedRole : null;
    }

    /**
     * @param FormEvent $event
     * @return null|CustomerUserRole
     */
    protected function getRole(FormEvent $event)
    {
        $role = $event->getData();

        return ($role instanceof CustomerUserRole && $role->getCustomer()) ? $role : null;
    }
}
