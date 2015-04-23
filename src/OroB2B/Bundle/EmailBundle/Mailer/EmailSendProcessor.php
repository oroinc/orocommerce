<?php

namespace OroB2B\Bundle\EmailBundle\Mailer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ApplicationBundle\Config\ConfigManager;

use OroB2B\Bundle\EmailBundle\Entity\EmailTemplate;
use OroB2B\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use OroB2B\Bundle\RFPBundle\Entity\Request;

class EmailSendProcessor
{
    const CREATE_REQUEST_TEMPLATE_NAME = 'request_create_notification';

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @param ManagerRegistry $registry
     * @param \Twig_Environment $twig
     * @param ConfigManager $configManager
     * @param Mailer $mailer
     */
    public function __construct(
        ManagerRegistry $registry,
        \Twig_Environment $twig,
        ConfigManager $configManager,
        Mailer $mailer
    ) {
        $this->manager = $registry->getManagerForClass('OroB2BEmailBundle:EmailTemplate');
        $this->twig = $twig;
        $this->configManager = $configManager;
        $this->mailer = $mailer;
    }

    /**
     * @param Request $request
     */
    public function sendRequestCreateNotification(Request $request)
    {
        $emailTo = $this->configManager->get('oro_b2b_rfp_admin.default_user_for_notifications');

        if ($emailTo) {
            $this->sendEmail($request, self::CREATE_REQUEST_TEMPLATE_NAME, $emailTo);
        }
    }

    /**
     * @param Request $request
     * @param string $templateName
     * @return int
     * @throws EmailTemplateNotFoundException
     */
    protected function sendEmail(Request $request, $templateName, $emailTo)
    {
        /** @var \OroB2B\Bundle\EmailBundle\Entity\EmailTemplate $emailTemplate */
        $emailTemplate = $this->manager
            ->getRepository('OroB2BEmailBundle:EmailTemplate')
            ->findOneBy(['name' => $templateName]);

        if (!$emailTemplate) {
            throw new EmailTemplateNotFoundException("Couldn't find email template with name " . $templateName);
        }

        //$this->assertEntity($emailTemplate, $request);

        $subject = $this->renderTemplate($emailTemplate->getSubject(), ['entity' => $request]);
        $body = $this->renderTemplate($emailTemplate->getContent(), ['entity' => $request]);

        return $this->mailer->send($subject, $body, $emailTo);
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

    /**
     * @param EmailTemplate $emailTemplate
     * @param mixed $entity
     * @throws \InvalidArgumentException
     */
    protected function assertEntity(EmailTemplate $emailTemplate, $entity)
    {
        $entityName = $emailTemplate->getEntityName();

        if (!($entity instanceof $entityName)) {
            throw new \InvalidArgumentException(
                sprintf('Entity variable should be instance of %s class', $entityName)
            );
        }
    }
}
