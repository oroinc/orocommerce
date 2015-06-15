<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\CustomerBundle\Mailer\Processor;

class AccountUserManager extends BaseUserManager implements ContainerAwareInterface
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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param AccountUser $user
     */
    public function register(AccountUser $user)
    {
        if ($this->getConfigValue('confirmation_required')) {
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
        if ($this->getConfigValue('confirmation_required')) {
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
     * @param string $name
     * @return array|string
     */
    protected function getConfigValue($name)
    {
        if (!$this->configManager) {
            $this->configManager = $this->container->get('oro_config.manager');
        }

        return $this->configManager->get($name);
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

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
