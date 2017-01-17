<?php

namespace Oro\Bundle\CustomerBundle\Mailer;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class Processor extends CustomerUserProcessor
{
    const WELCOME_EMAIL_TEMPLATE_NAME = 'customer_user_welcome_email';
    const CONFIRMATION_EMAIL_TEMPLATE_NAME = 'customer_user_confirmation_email';
    const RESET_PASSWORD_EMAIL_TEMPLATE_NAME = 'customer_user_reset_password';

    /**
     * @param CustomerUser $customerUser
     * @param string $password
     * @return int
     */
    public function sendWelcomeNotification(CustomerUser $customerUser, $password)
    {
        return $this->getEmailTemplateAndSendEmail(
            $customerUser,
            static::WELCOME_EMAIL_TEMPLATE_NAME,
            ['entity' => $customerUser, 'password' => $password]
        );
    }

    /**
     * @param CustomerUser $customerUser
     * @return int
     */
    public function sendConfirmationEmail(CustomerUser $customerUser)
    {
        return $this->getEmailTemplateAndSendEmail(
            $customerUser,
            static::CONFIRMATION_EMAIL_TEMPLATE_NAME,
            ['entity' => $customerUser, 'token' => $customerUser->getConfirmationToken()]
        );
    }

    /**
     * @param CustomerUser $customerUser
     * @return int
     */
    public function sendResetPasswordEmail(CustomerUser $customerUser)
    {
        return $this->getEmailTemplateAndSendEmail(
            $customerUser,
            static::RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            ['entity' => $customerUser]
        );
    }
}
