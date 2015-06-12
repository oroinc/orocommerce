<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\CustomerBundle\Mailer\Processor;

class AccountUserManager extends BaseUserManager
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Processor
     */
    protected $emailProcessor;

    /**
     * @param AccountUser $user
     */
    public function register(AccountUser $user)
    {
        if ($this->configManager->get('confirmation_required')) {
            $this->sendConfirmationEmail($user);
        } else {
            $this->confirmRegistration($user);
        }
    }

    /**
     * @param AccountUser $user
     */
    public function confirmRegistration(AccountUser $user)
    {
        $user->setEnabled(true);
        $this->sendWelcomeEmail($user);
    }

    /**
     * @param AccountUser $user
     */
    public function sendWelcomeEmail(AccountUser $user)
    {
        $this->emailProcessor->sendWelcomeNotification($user, $user->getPlainPassword());
    }

    /**
     * @param AccountUser $user
     */
    public function sendConfirmationEmail(AccountUser $user)
    {
        if ($this->configManager->get('confirmation_required')) {
            $user->setConfirmationToken($user->generateToken());
            $this->emailProcessor->sendConfirmationEmail($user, $user->getConfirmationToken());
        }
    }

    /**
     * @param int $maxLength
     * @return string
     */
    public function generatePassword($maxLength)
    {
        return substr($this->generateToken(), 0, $maxLength);
    }

    /**
     * @param ConfigManager $configManager
     * @return AccountUserManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;

        return $this;
    }

    /**
     * @param Processor $emailProcessor
     * @return AccountUserManager
     */
    public function setEmailProcessor(Processor $emailProcessor)
    {
        $this->emailProcessor = $emailProcessor;

        return $this;
    }

    /**
     * @return string
     */
    protected function generateToken()
    {
        return rtrim(strtr(base64_encode(hash('sha256', uniqid(mt_rand(), true), true)), '+/', '-_'), '=');
    }
}
