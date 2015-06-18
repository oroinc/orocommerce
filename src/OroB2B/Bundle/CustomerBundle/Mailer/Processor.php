<?php

namespace OroB2B\Bundle\CustomerBundle\Mailer;

use Oro\Bundle\UserBundle\Mailer\BaseProcessor;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class Processor extends BaseProcessor
{
    const WELCOME_EMAIL_TEMPLATE_NAME = 'account_user_welcome_email';
    const RESET_PASSWORD_EMAIL_TEMPLATE_NAME = 'account_user_reset_password';

    /**
     * @param AccountUser $accountUser
     * @param string $password
     * @return int
     */
    public function sendWelcomeNotification(AccountUser $accountUser, $password)
    {
        return $this->getEmailTemplateAndSendEmail(
            $accountUser,
            static::WELCOME_EMAIL_TEMPLATE_NAME,
            ['entity' => $accountUser, 'password' => $password]
        );
    }

    /**
     * @todo Implement confirmation email sending BB-613
     * @param AccountUser $accountUser
     * @param $confirmationToken
     * @return int
     */
    public function sendConfirmationEmail(AccountUser $accountUser, $confirmationToken)
    {
    }

    /**
     * @param AccountUser $accountUser
     * @return int
     */
    public function sendResetPasswordEmail(AccountUser $accountUser)
    {
        return $this->getEmailTemplateAndSendEmail(
            $accountUser,
            static::RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            ['entity' => $accountUser]
        );
    }
}
