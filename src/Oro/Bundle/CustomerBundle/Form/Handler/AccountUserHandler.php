<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

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

    /** @var TranslatorInterface */
    protected $translator;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param AccountUserManager $userManager
     * @param SecurityFacade $securityFacade
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        AccountUserManager $userManager,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
        $this->logger = $logger;
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
                        try {
                            $this->userManager->sendWelcomeEmail($accountUser);
                        } catch (\Exception $ex) {
                            $this->logger->error('Welcome email sending failed.', ['exception' => $ex]);
                            /** @var Session $session */
                            $session = $this->request->getSession();
                            $session->getFlashBag()->add(
                                'error',
                                $this->translator
                                    ->trans('oro.customer.controller.accountuser.welcome_failed.message')
                            );
                        }
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
