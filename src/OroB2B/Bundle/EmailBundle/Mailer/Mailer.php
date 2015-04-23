<?php

namespace OroB2B\Bundle\EmailBundle\Mailer;

use Oro\Bundle\ApplicationBundle\Config\ConfigManager;

use OroB2B\Bundle\EmailBundle\Entity\EmailTemplate;

class Mailer
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
     * @param ConfigManager $configManager
     */
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig, ConfigManager $configManager)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->configManager = $configManager;
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param mixed $entity
     * @param string $emailTo
     * @return integer
     */
    public function send(EmailTemplate $emailTemplate, $entity, $emailTo)
    {
        $this->assertEntity($emailTemplate, $entity);

        $emailFrom = $this->getMailFrom();

        $renderedTemplate = $this->renderTemplate($emailTemplate->getContent(), ['entity' => $entity]);
        $renderedSubject = $this->renderTemplate($emailTemplate->getSubject(), ['entity' => $entity]);

        $message = $this->mailer->createMessage();
        $message
            ->setSubject($renderedSubject)
            ->setFrom($emailFrom)
            ->setTo($emailTo)
            ->setBody($renderedTemplate);

        return $this->mailer->send($message);
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param mixed $entity
     * @throws \InvalidArgumentException
     */
    protected function assertEntity(EmailTemplate $emailTemplate, $entity)
    {
        if (!is_object($entity)) {
            throw new \InvalidArgumentException('Entity variable should be an object');
        }

        $entityName = $emailTemplate->getEntityName();

        if (!($entity instanceof $entityName)) {
            throw new \InvalidArgumentException(
                sprintf('Entity variable should be instance of %s class', $entityName)
            );
        }
    }

    /**
     * @return array
     */
    protected function getMailFrom()
    {
        $emailFrom = $this->configManager->get('oro_notification.email_notification_sender_email');
        $nameFrom = $this->configManager->get('oro_notification.email_notification_sender_name');

        return [$emailFrom => $nameFrom];
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function renderTemplate($template, array $data)
    {
        return $this->twig->render($template, $data);
    }
}
