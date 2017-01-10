<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;

abstract class AbstractAccountUserPasswordHandler
{
    /**
     * @var CustomerUserManager
     */
    protected $userManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param CustomerUserManager $userManager
     * @param TranslatorInterface $translator
     */
    public function __construct(CustomerUserManager $userManager, TranslatorInterface $translator)
    {
        $this->userManager = $userManager;
        $this->translator = $translator;
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return CustomerUser|bool
     */
    abstract public function process(FormInterface $form, Request $request);

    /**
     * @param FormInterface $form
     * @param string $message
     * @param array $messageParameters
     */
    protected function addFormError(FormInterface $form, $message, array $messageParameters = [])
    {
        $message = $this->translator->trans($message, $messageParameters);
        $form->addError(new FormError($message));
    }
}
