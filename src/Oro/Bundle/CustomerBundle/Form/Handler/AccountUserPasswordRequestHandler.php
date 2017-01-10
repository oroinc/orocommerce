<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class AccountUserPasswordRequestHandler extends AbstractAccountUserPasswordHandler
{
    /**
     * @param FormInterface $form
     * @param Request $request
     * @return CustomerUser|bool
     */
    public function process(FormInterface $form, Request $request)
    {
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $emailForm = $form->get('email');
                $email = $emailForm->getData();

                /** @var CustomerUser $user */
                $user = $this->userManager->findUserByUsernameOrEmail($email);
                if ($this->validateUser($emailForm, $email, $user)) {
                    if (null === $user->getConfirmationToken()) {
                        $user->setConfirmationToken($user->generateToken());
                    }

                    try {
                        $this->userManager->sendResetPasswordEmail($user);
                        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
                        $this->userManager->updateUser($user);

                        return $user;
                    } catch (\Exception $e) {
                        $this->addFormError($form, 'oro.email.handler.unable_to_send_email');
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param FormInterface $form
     * @param string $email
     * @param CustomerUser|null $user
     * @return bool
     */
    protected function validateUser(FormInterface $form, $email, CustomerUser $user = null)
    {
        if (!$user) {
            $this->addFormError($form, 'oro.customer.customeruser.profile.email_not_exists', ['%email%' => $email]);

            return false;
        }

        return true;
    }
}
