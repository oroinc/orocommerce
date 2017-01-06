<?php

namespace Oro\Bundle\RFPBundle\Mailer;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\BaseProcessor;
use Oro\Bundle\RFPBundle\Entity\Request;

class Processor extends BaseProcessor implements LoggerAwareInterface
{
    const CREATE_REQUEST_TEMPLATE_NAME = 'request_create_notification';

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param User    $user
     *
     * @return int
     */
    public function sendRFPNotification(Request $request, User $user)
    {
        try {
            return $this->getEmailTemplateAndSendEmail(
                $user,
                static::CREATE_REQUEST_TEMPLATE_NAME,
                ['entity' => $request]
            );
        } catch (\Swift_SwiftException $exception) {
            if (null !== $this->logger) {
                $this->logger->error('Unable to send RFP notification email', ['exception' => $exception]);
            }
        }

        return 0;
    }
}
