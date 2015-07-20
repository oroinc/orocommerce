<?php

namespace OroB2B\Bundle\RFPBundle\Mailer;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\BaseProcessor;
use OroB2B\Bundle\RFPBundle\Entity\Request;

class Processor extends BaseProcessor
{
    const CREATE_REQUEST_TEMPLATE_NAME = 'request_create_notification';

    /**
     * @param Request $request
     * @param User $user
     * @return int
     */
    public function sendRFPNotification(Request $request, User $user)
    {
        return $this->getEmailTemplateAndSendEmail(
            $user,
            static::CREATE_REQUEST_TEMPLATE_NAME,
            ['entity' => $request]
        );
    }
}
