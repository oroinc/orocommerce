<?php

namespace Oro\Bundle\SaleBundle\Notification;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\SaleBundle\Entity\Quote;

class NotificationHelper extends Controller
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var EmailModelBuilder */
    protected $emailModelBuilder;

    /** @var Processor */
    protected $emailProcessor;

    /** @var string */
    protected $quoteClassName;

    /** @var string */
    protected $emailTemplateClassName;

    /**
     * @param ManagerRegistry $registry
     * @param EmailModelBuilder $emailModelBuilder
     * @param Processor $emailProcessor
     */
    public function __construct(
        ManagerRegistry $registry,
        EmailModelBuilder $emailModelBuilder,
        Processor $emailProcessor
    ) {
        $this->registry = $registry;
        $this->emailModelBuilder = $emailModelBuilder;
        $this->emailProcessor = $emailProcessor;
    }

    /**
     * @param string $quoteClassName
     * @return NotificationHelper
     */
    public function setQuoteClassName($quoteClassName)
    {
        $this->quoteClassName = $quoteClassName;

        return $this;
    }

    /**
     * @param string $emailTemplateClassName
     * @return NotificationHelper
     */
    public function setEmailTemplateClassName($emailTemplateClassName)
    {
        $this->emailTemplateClassName = $emailTemplateClassName;

        return $this;
    }

    /**
     * @param Quote $quote
     * @return Email
     */
    public function getEmailModel(Quote $quote)
    {
        return $this->createEmailModel($quote);
    }

    /**
     * @param Email $emailModel
     */
    public function send(Email $emailModel)
    {
        $this->emailProcessor->process($emailModel);
    }

    /**
     * @param Quote $quote
     * @return Email
     */
    protected function createEmailModel(Quote $quote)
    {
        $emailModel = $this->emailModelBuilder->createEmailModel();
        $emailModel
            ->setEntityClass($this->quoteClassName)
            ->setEntityId($quote->getId())
            ->setTo([$quote->getEmail()])
            ->setContexts([$quote])
            ->setTemplate($this->getEmailTemplate('quote_email_link'));

        return $emailModel;
    }

    /**
     * @param string $name
     * @return EmailTemplate|null
     */
    protected function getEmailTemplate($name)
    {
        return $this->getRepository($this->emailTemplateClassName)->findOneBy(['name' => $name]);
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getManager($className)
    {
        return $this->registry->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getManager($className)->getRepository($className);
    }
}
