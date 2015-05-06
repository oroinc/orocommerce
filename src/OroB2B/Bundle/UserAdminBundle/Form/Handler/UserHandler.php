<?php

namespace OroB2B\Bundle\UserAdminBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\UserAdminBundle\Entity\User;

class UserHandler
{
    /** @var FormInterface  */
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
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param User $user
     * @return bool True on successful processing, false otherwise
     */
    public function process(User $user)
    {
        $this->form->setData($user);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->manager->persist($user);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
