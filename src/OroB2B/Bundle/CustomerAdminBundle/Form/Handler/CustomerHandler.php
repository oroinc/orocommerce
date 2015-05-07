<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomer;

class CustomerHandler
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param AbstractCustomer $customer
     * @return bool True on successful processing, false otherwise
     */
    public function process(AbstractCustomer $customer)
    {
        $this->form->setData($customer);

        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->manager->persist($customer);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
