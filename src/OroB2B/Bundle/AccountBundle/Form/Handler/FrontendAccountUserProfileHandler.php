<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserManager;

class FrontendAccountUserProfileHandler
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
                    $this->userManager->register($accountUser);
                }

                $this->userManager->updateUser($accountUser);
                $this->userManager->reloadUser($accountUser);

                return true;
            }
        }

        return false;
    }
}
