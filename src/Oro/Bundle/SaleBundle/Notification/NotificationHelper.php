<?php

namespace Oro\Bundle\SaleBundle\Notification;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * This helper contains useful methods common for working with quote emails.
 */
class NotificationHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $registry;

    private EmailModelBuilder $emailModelBuilder;

    private EmailModelSender $emailModelSender;

    private FeatureChecker $featureChecker;

    private ?string $quoteClassName = null;

    private ?string $emailTemplateClassName = null;

    private bool $enabled = true;

    public function __construct(
        ManagerRegistry $registry,
        EmailModelBuilder $emailModelBuilder,
        EmailModelSender $emailModelSender,
        FeatureChecker $featureChecker
    ) {
        $this->registry = $registry;
        $this->emailModelBuilder = $emailModelBuilder;
        $this->emailModelSender = $emailModelSender;
        $this->featureChecker = $featureChecker;
        $this->logger = new NullLogger();
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

        try {
            $this->emailModelSender->send($emailModel);
        } catch (\RuntimeException $exception) {
            $this->logger->error(
                sprintf(
                    'Failed to send email model to %s: %s',
                    implode(', ', $emailModel->getTo()),
                    $exception->getMessage()
                ),
                ['exception' => $exception, 'emailModel' => $emailModel]
            );
        }
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

        if ($quote->getOrganization()) {
            $emailModel->setOrganization($quote->getOrganization());
        }

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
