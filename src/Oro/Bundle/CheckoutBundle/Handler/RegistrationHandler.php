<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Form\Handler\FrontendCustomerUserHandler;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRegistrationFormProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handling registration request during checkout
 */
class RegistrationHandler
{
    /**
     * @var FrontendCustomerUserRegistrationFormProvider
     */
    private $registrationFormProvider;

    /**
     * @var CustomerUserManager
     */
    private $customerUserManager;

    /**
     * @var FrontendCustomerUserHandler
     */
    private $customerUserHandler;

    /**
     * @var UpdateHandlerFacade
     */
    private $updateHandlerFacade;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param FrontendCustomerUserRegistrationFormProvider $registrationFormProvider
     * @param CustomerUserManager $customerUserManager
     * @param FrontendCustomerUserHandler $customerUserHandler
     * @param UpdateHandlerFacade $updateHandlerFacade
     * @param TranslatorInterface $translator
     */
    public function __construct(
        FrontendCustomerUserRegistrationFormProvider $registrationFormProvider,
        CustomerUserManager $customerUserManager,
        FrontendCustomerUserHandler $customerUserHandler,
        UpdateHandlerFacade $updateHandlerFacade,
        TranslatorInterface $translator
    ) {
        $this->registrationFormProvider = $registrationFormProvider;
        $this->customerUserManager = $customerUserManager;
        $this->customerUserHandler = $customerUserHandler;
        $this->updateHandlerFacade = $updateHandlerFacade;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_POST)
            || $request->get('isRegistration') === null
        ) {
            return false;
        }
        $form = $this->registrationFormProvider->getRegisterForm();
        if (!$form) {
            return false;
        }
        $registrationMessage = 'oro.customer.controller.customeruser.registered.message';
        if ($this->customerUserManager->isConfirmationRequired()) {
            $registrationMessage = 'oro.customer.controller.customeruser.registered_with_confirmation.message';
        }

        $this->updateHandlerFacade->update(
            $form->getData(),
            $form,
            $this->translator->trans($registrationMessage),
            $request,
            $this->customerUserHandler
        );

        return $form->isSubmitted() && $form->isValid();
    }
}
