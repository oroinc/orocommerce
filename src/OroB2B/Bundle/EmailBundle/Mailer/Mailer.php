<?php

namespace OroB2B\Bundle\EmailBundle\Mailer;

use Oro\Bundle\ApplicationBundle\Config\ConfigManager;

class Mailer
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param \Swift_Mailer $mailer
     * @param ConfigManager $configManager
     */
    public function __construct(\Swift_Mailer $mailer, ConfigManager $configManager)
    {
        $this->mailer = $mailer;
        $this->configManager = $configManager;
    }

    /**
     * @param string $subject
     * @param string $body
     * @param string $emailTo
     * @return integer
     */
    public function send($subject, $body, $emailTo)
    {
        $result = 0;
        $emailFrom = $this->getMailFrom();

        if ($emailFrom) {
            $message = $this->mailer->createMessage();
            $message
                ->setSubject($subject)
                ->setBody($body)
                ->setFrom($emailFrom)
                ->setTo($emailTo);

            $result = $this->mailer->send($message);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getMailFrom()
    {
        return $this->configManager->get('oro_b2b_rfp_admin.default_user_for_notifications');
    }
}
