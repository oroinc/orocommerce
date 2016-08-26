<?php

namespace Oro\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserRole;

class FrontendAccountUserRoleType extends AbstractAccountUserRoleType
{
    const NAME = 'orob2b_account_frontend_account_user_role';

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

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'updateAccountUsers']);
    }

    /**
     * @param FormEvent $event
     */
    public function updateAccountUsers(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();

        $predefinedRole = $options['predefined_role'];
        if (!$predefinedRole instanceof AccountUserRole) {
            return;
        }

        $role = $event->getData();
        if (!$role instanceof AccountUserRole || !$role->getAccount()) {
            return;
        }

        $accountUsers = $predefinedRole->getAccountUsers()->filter(
            function (AccountUser $accountUser) use ($role) {
                return $accountUser->getAccount() &&
                    $accountUser->getAccount()->getId() === $role->getAccount()->getId();
            }
        );

        $accountUsers->map(
            function (AccountUser $accountUser) use ($predefinedRole) {
                $accountUser->removeRole($predefinedRole);
            }
        );

        $event->getForm()->get('appendUsers')->setData($accountUsers->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'access_level_route' => 'orob2b_account_frontend_acl_access_levels',
                'predefined_role' => null,
            ]
        );
    }
}
