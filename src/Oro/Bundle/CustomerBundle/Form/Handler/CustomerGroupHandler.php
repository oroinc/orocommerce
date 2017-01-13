<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Event\CustomerGroupEvent;

class CustomerGroupHandler
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
                    $this->form->get('appendCustomers')->getData(),
                    $this->form->get('removeCustomers')->getData()
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
     * @param Customer[] $append
     * @param Customer[] $remove
     */
    protected function onSuccess(CustomerGroup $entity, array $append, array $remove)
    {
        $this->setGroup($entity, $append);
        $this->removeFromGroup($entity, $remove);
        $event = new CustomerGroupEvent($entity, $this->form);
        $this->dispatcher->dispatch(CustomerGroupEvent::BEFORE_FLUSH, $event);
        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * Append customers to customer group
     *
     * @param CustomerGroup $group
     * @param Customer[] $customers
     */
    protected function setGroup(CustomerGroup $group, array $customers)
    {
        foreach ($customers as $customer) {
            $customer->setGroup($group);
            $this->manager->persist($customer);
        }
    }

    /**
     * Remove users from business unit
     *
     * @param CustomerGroup $group
     * @param Customer[] $customers
     */
    protected function removeFromGroup(CustomerGroup $group, array $customers)
    {
        foreach ($customers as $customer) {
            if ($customer->getGroup()->getId() === $group->getId()) {
                $customer->setGroup(null);
                $this->manager->persist($customer);
            }
        }
    }
}
