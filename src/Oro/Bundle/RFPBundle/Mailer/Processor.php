<?php

namespace Oro\Bundle\RFPBundle\Mailer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Mailer\BaseProcessor;
use Oro\Bundle\RFPBundle\Entity\Request;

class Processor extends BaseProcessor implements LoggerAwareInterface
{
    const CREATE_REQUEST_TEMPLATE_NAME = 'request_create_notification';
    const CONFIRM_REQUEST_TEMPLATE_NAME = 'request_create_confirmation';

    /** @var LoggerInterface */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        EmailRenderer $renderer,
        EmailHolderHelper $emailHolderHelper,
        \Swift_Mailer $mailer
    ) {
        parent::__construct($managerRegistry, $configManager, $renderer, $emailHolderHelper, $mailer);

        $this->logger = new NullLogger();
    }

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
        return $this->send($request, $user, self::CREATE_REQUEST_TEMPLATE_NAME);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     *
     * @return int
     */
    public function sendConfirmation(Request $request, UserInterface $user)
    {
        return $this->send($request, $user, self::CONFIRM_REQUEST_TEMPLATE_NAME);
    }

    /**
     * @param Request $request
     * @param UserInterface $user
     * @param string $template
     *
     * @return int
     */
    private function send(Request $request, UserInterface $user, $template)
    {
        try {
            return $this->getEmailTemplateAndSendEmail($user, $template, ['entity' => $request]);
        } catch (\Swift_SwiftException $exception) {
            $this->logger->error('Unable to send email', [
                'template' => $template,
                'username' => $user->getUsername(),
                'request' => (string) $request,
                'exception' => $exception,
            ]);
        }

        return 0;
    }
}
