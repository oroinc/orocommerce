<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Event\AccountGroupEvent;

class AccountGroupHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Process form
     *
     * @param CustomerGroup $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(CustomerGroup $entity)
    {
        $this->form->setData($entity);

        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess(
                    $entity,
                    $this->form->get('appendAccounts')->getData(),
                    $this->form->get('removeAccounts')->getData()
                );

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param CustomerGroup $entity
     * @param Account[] $append
     * @param Account[] $remove
     */
    protected function onSuccess(CustomerGroup $entity, array $append, array $remove)
    {
        $this->setGroup($entity, $append);
        $this->removeFromGroup($entity, $remove);
        $event = new AccountGroupEvent($entity, $this->form);
        $this->dispatcher->dispatch(AccountGroupEvent::BEFORE_FLUSH, $event);
        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * Append accounts to account group
     *
     * @param CustomerGroup $group
     * @param Account[] $accounts
     */
    protected function setGroup(CustomerGroup $group, array $accounts)
    {
        foreach ($accounts as $account) {
            $account->setGroup($group);
            $this->manager->persist($account);
        }
    }

    /**
     * Remove users from business unit
     *
     * @param CustomerGroup $group
     * @param Account[] $accounts
     */
    protected function removeFromGroup(CustomerGroup $group, array $accounts)
    {
        foreach ($accounts as $account) {
            if ($account->getGroup()->getId() === $group->getId()) {
                $account->setGroup(null);
                $this->manager->persist($account);
            }
        }
    }
}
