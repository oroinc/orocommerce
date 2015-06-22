<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class AccountUserPasswordResetHandler extends AbstractAccountUserPasswordHandler
{
    /**
     * @param FormInterface $form
     * @param Request $request
     * @return bool
     */
    public function process(FormInterface $form, Request $request)
    {
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                /** @var AccountUser $user */
                $user = $form->getData();

                $user
                    ->setConfirmed(true)
                    ->setConfirmationToken(null)
                    ->setPasswordRequestedAt(null);

                $this->userManager->updateUser($user);

                return true;
            }
        }

        return false;
    }
}
