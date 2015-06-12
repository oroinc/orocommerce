<?php

namespace OroB2B\Bundle\CustomerBundle\Mailer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class Processor
{
    const WELCOME_EMAIL_TEMPLATE_NAME = 'welcome_email';

    /** @var ObjectManager */
    protected $objectManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var \Swift_Mailer */
    protected $mailer;

    /**
     * @param ObjectManager $objectManager
     * @param ConfigManager $configManager
     * @param EmailRenderer $renderer
     * @param \Swift_Mailer $mailer
     */
    public function __construct(
        ObjectManager $objectManager,
        ConfigManager $configManager,
        EmailRenderer $renderer,
        \Swift_Mailer $mailer = null
    ) {
        $this->objectManager = $objectManager;
        $this->configManager = $configManager;
        $this->renderer      = $renderer;
        $this->mailer        = $mailer;
    }

    /**
     * @param AccountUser $accountUser
     * @param string $password
     * @return bool
     */
    public function sendWelcomeNotification(AccountUser $accountUser, $password)
    {
        $emailTemplate = $this->objectManager
            ->getRepository('OroEmailBundle:EmailTemplate')
            ->findByName(self::WELCOME_EMAIL_TEMPLATE_NAME);

        $templateData = $this->renderer->compileMessage(
            $emailTemplate,
            ['entity' => $accountUser, 'password' => $password]
        );
        $type = $emailTemplate->getType() == 'txt' ? 'text/plain' : 'text/html';

        return $this->sendEmail($accountUser, $templateData, $type);
    }

    /**
     * @todo Implement confirmation email sending
     * @param AccountUser $accountUser
     * @param $confirmationToken
     */
    public function sendConfirmationEmail(AccountUser $accountUser, $confirmationToken)
    {
    }

    /**
     * @param AccountUser $accountUser
     * @param array $templateData
     * @param string $type
     *
     * @return bool
     */
    protected function sendEmail(AccountUser $accountUser, array $templateData, $type)
    {
        list ($subjectRendered, $templateRendered) = $templateData;

        $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $senderName  = $this->configManager->get('oro_notification.email_notification_sender_name');

        $message = \Swift_Message::newInstance()
            ->setSubject($subjectRendered)
            ->setFrom($senderEmail, $senderName)
            ->setTo($accountUser->getEmail())
            ->setBody($templateRendered, $type);

        $this->mailer->send($message);
    }
}
