<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserRoleHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param AccountUserRole $role
     * @return bool
     */
    public function process(AccountUserRole $role)
    {
        $this->form->setData($role);

        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess(
                    $role,
                    $this->form->get('appendUsers')->getData(),
                    $this->form->get('removeUsers')->getData()
                );

                return true;
            }
        }

        return false;
    }

    /**
     * @param AccountUserRole $role
     * @param AccountUser[] $append
     * @param AccountUser[] $remove
     */
    protected function onSuccess(AccountUserRole $role, array $append, array $remove)
    {
        $this->appendUsers($role, $append);
        $this->removeUsers($role, $remove);
        $this->manager->persist($role);
        $this->manager->flush();
    }

    /**
     * @param AccountUserRole $role
     * @param AccountUser[] $users
     */
    protected function appendUsers(AccountUserRole $role, array $users)
    {
        foreach ($users as $user) {
            $user->addRole($role);
        }
    }

    /**
     * @param AccountUserRole $role
     * @param AccountUser[] $users
     */
    protected function removeUsers(AccountUserRole $role, array $users)
    {
        foreach ($users as $user) {
            $user->removeRole($role);
        }
    }
}
