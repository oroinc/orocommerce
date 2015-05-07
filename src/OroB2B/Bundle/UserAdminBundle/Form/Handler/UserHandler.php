<?php

namespace OroB2B\Bundle\UserAdminBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\UserBundle\Util\TokenGenerator;

use OroB2B\Bundle\UserAdminBundle\Entity\User;
use OroB2B\Bundle\UserAdminBundle\Mailer\Processor;

class UserHandler
{
    /** @var FormInterface  */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var Processor */
    protected $emailSendProcessor;

    /** @var TokenGenerator */
    protected $passwordGenerator;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param Processor $emailSendProcessor
     * @param TokenGenerator $passwordGenerator
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        Processor $emailSendProcessor,
        TokenGenerator $passwordGenerator
    ) {
        $this->form               = $form;
        $this->request            = $request;
        $this->manager            = $manager;
        $this->emailSendProcessor = $emailSendProcessor;
        $this->passwordGenerator  = $passwordGenerator;
    }

    /**
     * Process form
     *
     * @param User $user
     * @return bool True on successful processing, false otherwise
     */
    public function process(User $user)
    {
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                if (!$user->getId()) {
                    if ($this->form->get('passwordGenerate')->getData()) {
                        $generatedPassword = substr($this->passwordGenerator->generateToken(), 0, 10);
                        $user->setPlainPassword($generatedPassword);
                    }

                    if ($this->form->get('sendEmail')->getData()) {
                        $password = $user->getPlainPassword();
                        $this->emailSendProcessor->sendWelcomeNotification($user, $password);
                    }
                }

                $this->manager->persist($user);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
