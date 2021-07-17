<?php

namespace Oro\Bundle\SaleBundle\Notification;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;

/**
 * This helper contains useful methods common for working with quote emails.
 */
class NotificationHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var EmailModelBuilder */
    protected $emailModelBuilder;

    /** @var Processor */
    protected $emailProcessor;

    /** @var FeatureChecker */
    protected $featureChecker;

    /** @var string */
    protected $quoteClassName;

    /** @var string */
    protected $emailTemplateClassName;

    /** @var bool */
    protected $enabled = true;

    public function __construct(
        ManagerRegistry $registry,
        EmailModelBuilder $emailModelBuilder,
        Processor $emailProcessor,
        FeatureChecker $featureChecker
    ) {
        $this->registry = $registry;
        $this->emailModelBuilder = $emailModelBuilder;
        $this->emailProcessor = $emailProcessor;
        $this->featureChecker = $featureChecker;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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

    public function send(Email $emailModel)
    {
        if (!$this->enabled) {
            return;
        }

        $this->emailProcessor->process($emailModel);
    }

    /**
     * @param Quote $quote
     * @return Email
     */
    protected function createEmailModel(Quote $quote)
    {
        $this->applyEntityContext($quote);

        $emailModel = $this->emailModelBuilder->createEmailModel();
        $emailModel
            ->setEntityClass($this->quoteClassName)
            ->setEntityId($quote->getId())
            ->setTo([$quote->getEmail()])
            ->setContexts([$quote])
            ->setTemplate(
                $this->getEmailTemplate(
                    $this->isGuestAccessTemplateApplicable($quote) ? 'quote_email_link_guest' : 'quote_email_link'
                )
            );

        return $emailModel;
    }

    protected function applyEntityContext(Quote $quote)
    {
        // pass entityClass end entityId to request, because no way to set up entityClass and entityId as arguments
        $request = new Request(['entityClass' => $this->quoteClassName, 'entityId' => $quote->getId()]);
        $request->setMethod('GET');

        $this->emailModelBuilder->setRequest($request);
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

    private function isGuestAccessTemplateApplicable(Quote $quote): bool
    {
        return $this->featureChecker &&
            $this->featureChecker->isFeatureEnabled('guest_quote') &&
            (!$quote->getCustomerUser() || $quote->getCustomerUser()->isGuest());
    }
}
