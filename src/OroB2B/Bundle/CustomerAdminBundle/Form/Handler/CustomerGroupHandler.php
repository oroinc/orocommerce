<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;
use OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup;

class CustomerGroupHandler
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
                $append = $this->form->get('appendCustomers')->getData();
                $remove = $this->form->get('removeCustomers')->getData();
                $this->onSuccess($entity, $append, $remove);

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
