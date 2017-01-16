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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
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
    public function preSubmit(FormEvent $event)
    {
        $this->updateCustomerUsers($event);
    }

    /**
     * @param FormEvent $event
     */
    protected function updateCustomerUsers(FormEvent $event)
    {
        $data = $event->getData();
        $predefinedRole = $this->getPredefinedRole($event);

        if (!isset($data['customer'])) {
            return;
        }

        $customerId = $data['customer'];

        if (!$customerId || !$predefinedRole) {
            return;
        }

        $customerUsers = $predefinedRole->getCustomerUsers()->filter(
            function (CustomerUser $customerUser) use ($customerId) {
                return $customerUser->getCustomer() &&
                    $customerUser->getCustomer()->getId() === (int)$customerId;
            }
        );

        $customerUsersIds = $customerUsers->map(function (CustomerUser $customerUser) {
            return $customerUser->getId();
        })->toArray();

        $appendUsersIds = explode(',', $data['appendUsers']);
        $appendUsersIds = array_filter($appendUsersIds, 'strlen');

        $usersToAppend = array_merge($customerUsersIds, $appendUsersIds);

        $removedUsersIds = explode(',', $data['removeUsers']);
        $removedUsersIds = array_filter($removedUsersIds, 'strlen');

        foreach ($removedUsersIds as $removedUserId) {
            if ($key = array_search($removedUserId, $usersToAppend)) {
                unset($usersToAppend[$key]);
            }
        }

        $data['appendUsers'] = implode(',', $usersToAppend);
        $event->setData($data);
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
        foreach ($form->get('appendUsers')->getData() as $customerUser) {
            $addedUsers->attach($customerUser);
        }

        foreach ($form->get('removeUsers')->getData() as $customerUser) {
            $addedUsers->detach($customerUser);
        }

        foreach ($addedUsers as $customerUser) {
            $predefinedRole->removeCustomerUser($customerUser);
            $customerUser->removeRole($predefinedRole);
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
