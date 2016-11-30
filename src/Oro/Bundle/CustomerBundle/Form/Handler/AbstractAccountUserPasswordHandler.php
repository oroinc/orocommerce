<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserManager;

abstract class AbstractAccountUserPasswordHandler
{
    /**
     * @var AccountUserManager
     */
    protected $userManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param AccountUserManager $userManager
     * @param TranslatorInterface $translator
     */
    public function __construct(AccountUserManager $userManager, TranslatorInterface $translator)
    {
        $this->userManager = $userManager;
        $this->translator = $translator;
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @return AccountUser|bool
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
