<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserManager;

class AccountUserHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var AccountUserManager */
    protected $userManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param AccountUserManager $userManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        AccountUserManager $userManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
    }

    /**
     * Process form
     *
     * @param AccountUser $accountUser
     * @return bool True on successful processing, false otherwise
     */
    public function process(AccountUser $accountUser)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                if (!$accountUser->getId()) {
                    if ($this->form->get('passwordGenerate')->getData()) {
                        $generatedPassword = $this->userManager->generatePassword(10);
                        $accountUser->setPlainPassword($generatedPassword);
                    }

                    if ($this->form->get('sendEmail')->getData()) {
                        $this->userManager->sendWelcomeEmail($accountUser);
                    }
                }

                $this->userManager->updateUser($accountUser);

                return true;
            }
        }

        return false;
    }
}
