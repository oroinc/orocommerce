<?php

namespace OroB2B\Bundle\AccountBundle\Mailer;

use Oro\Bundle\UserBundle\Mailer\BaseProcessor;
use Oro\Bundle\UserBundle\Entity\UserInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserProcessor extends BaseProcessor
{
    /**
     * @param UserInterface|AccountUser $user
     * @return string
     */
    protected function getSenderEmail(UserInterface $user)
    {
        return $this->configManager->get(
            'oro_notification.email_notification_sender_email',
            false,
            false,
            $user->getWebsite()
        );
    }

    /**
     * @param UserInterface|AccountUser $user
     * @return string
     */
    protected function getSenderName(UserInterface $user)
    {
        return $this->configManager->get(
            'oro_notification.email_notification_sender_name',
            false,
            false,
            $user->getWebsite()
        );
    }
}
