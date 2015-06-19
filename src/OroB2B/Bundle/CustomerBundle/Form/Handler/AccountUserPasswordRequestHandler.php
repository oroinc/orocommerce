<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class AccountUserPasswordRequestHandler extends AbstractAccountUserPasswordHandler
{
    /**
     * @param FormInterface $form
     * @param Request $request
     * @return AccountUser|bool
     */
    public function process(FormInterface $form, Request $request)
    {
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $email = $form->get('email')->getData();

                /** @var AccountUser $user */
                $user = $this->userManager->findUserByUsernameOrEmail($email);
                if ($this->validateUser($form, $email, $user)) {
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
     * @param AccountUser|null $user
     * @return bool
     */
    protected function validateUser(FormInterface $form, $email, AccountUser $user = null)
    {
        if (!$user) {
            $this->addFormError($form, 'orob2b.customer.accountuser.profile.email_not_exists', ['%email%' => $email]);

            return false;
        } elseif ($user->isPasswordRequestNonExpired($this->ttl)) {
            $this->addFormError(
                $form,
                'orob2b.customer.accountuser.profile.password.reset.ttl_already_requested.message'
            );

            return false;
        }

        return true;
    }
}
