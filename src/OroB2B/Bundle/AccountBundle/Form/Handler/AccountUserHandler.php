<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserManager;

class AccountUserHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var AccountUserManager */
    protected $userManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param AccountUserManager $userManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        AccountUserManager $userManager,
        SecurityFacade $securityFacade
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
        $this->securityFacade = $securityFacade;
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

                $token = $this->securityFacade->getToken();
                if ($token instanceof OrganizationContextTokenInterface) {
                    $organization = $token->getOrganizationContext();
                    $accountUser->setOrganization($organization)
                        ->addOrganization($organization);
                }

                $this->userManager->updateUser($accountUser);

                return true;
            }
        }

        return false;
    }
}
