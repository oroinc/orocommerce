<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Mailer\Processor;

class AccountUserHandler
{
    /** @var FormInterface  */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var Processor */
    protected $emailSendProcessor;

    /** @var BaseUserManager */
    protected $userManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param Processor $emailSendProcessor
     * @param BaseUserManager $userManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        Processor $emailSendProcessor,
        BaseUserManager $userManager
    ) {
        $this->form               = $form;
        $this->request            = $request;
        $this->manager            = $manager;
        $this->emailSendProcessor = $emailSendProcessor;
        $this->userManager        = $userManager;
    }

    /**
     * Process form
     *
     * @param AccountUser $accountUser
     * @return bool True on successful processing, false otherwise
     */
    public function process(AccountUser $accountUser)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                if (!$accountUser->getId()) {
                    if ($this->form->get('passwordGenerate')->getData()) {
                        $generatedPassword = substr($this->generateToken(), 0, 10);
                        $accountUser->setPlainPassword($generatedPassword);
                    }

                    if ($this->form->get('sendEmail')->getData()) {
                        $password = $accountUser->getPlainPassword();
                        $this->emailSendProcessor->sendWelcomeNotification($accountUser, $password);
                    }
                }

                $this->userManager->updatePassword($accountUser);

                $this->manager->persist($accountUser);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function generateToken()
    {
        return rtrim(strtr(base64_encode(hash('sha256', uniqid(mt_rand(), true), true)), '+/', '-_'), '=');
    }
}
