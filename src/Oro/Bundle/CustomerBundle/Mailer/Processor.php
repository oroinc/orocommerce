<?php

namespace Oro\Bundle\CustomerBundle\Mailer;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class Processor extends AccountUserProcessor
{
    const WELCOME_EMAIL_TEMPLATE_NAME = 'account_user_welcome_email';
    const CONFIRMATION_EMAIL_TEMPLATE_NAME = 'account_user_confirmation_email';
    const RESET_PASSWORD_EMAIL_TEMPLATE_NAME = 'account_user_reset_password';

    /**
     * @param CustomerUser $accountUser
     * @param string $password
     * @return int
     */
    public function sendWelcomeNotification(CustomerUser $accountUser, $password)
    {
        return $this->getEmailTemplateAndSendEmail(
            $accountUser,
            static::WELCOME_EMAIL_TEMPLATE_NAME,
            ['entity' => $accountUser, 'password' => $password]
        );
    }

    /**
     * @param CustomerUser $accountUser
     * @return int
     */
    public function sendConfirmationEmail(CustomerUser $accountUser)
    {
        return $this->getEmailTemplateAndSendEmail(
            $accountUser,
            static::CONFIRMATION_EMAIL_TEMPLATE_NAME,
            ['entity' => $accountUser, 'token' => $accountUser->getConfirmationToken()]
        );
    }

    /**
     * @param CustomerUser $accountUser
     * @return int
     */
    public function sendResetPasswordEmail(CustomerUser $accountUser)
    {
        return $this->getEmailTemplateAndSendEmail(
            $accountUser,
            static::RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            ['entity' => $accountUser]
        );
    }
}
