<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fixture for email notifications
 */
class LoadTransitionEmailNotifications extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const WORKFLOW_NAME = 'b2b_quote_backoffice_approvals';

    /** @var array */
    protected static $notifications = [
        'quote_accepted' => 'accept_transition',
        'quote_approved' => 'approve_transition',
        'quote_approved_and_sent_to_customer' => 'approve_and_send_to_customer_transition',
        'quote_cancelled' => 'cancel_transition',
        'quote_created' => '__start__',
        'quote_declined' => 'decline_transition',
        'quote_declined_by_customer' => 'decline_by_customer_transition',
        'quote_deleted' => 'delete_transition',
        'quote_edited' => 'edit_transition',
        'quote_expired_automatic' => 'auto_expire_transition',
        'quote_expired_manual' => 'expire_transition',
        'quote_not_approved' => 'decline_by_reviewer_transition',
        'quote_reopened' => 'reopen_transition',
        'quote_restored' => 'return_transition',
        'quote_sent_to_customer' => 'send_to_customer_transition',
        'quote_submitted_for_review' => 'submit_for_review_transition',
        'quote_under_review' => 'review_transition'
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadEmailNotificationTemplates::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $workflow = $this->getWorkflowDefinition($manager);

        foreach (self::$notifications as $emailTemplateName => $transitionName) {
            $emailTemplate = $this->getEmailTemplate($manager, $emailTemplateName);

            $recipientList = new RecipientList();
            $recipientList->setAdditionalEmailAssociations(['owner']);

            $manager->persist($recipientList);

            $entity = new EmailNotification();
            $entity->setEntityName(Quote::class)
                ->setEventName(WorkflowEvents::NOTIFICATION_TRANSIT_EVENT)
                ->setTemplate($emailTemplate)
                ->setRecipientList($recipientList)
                ->setWorkflowDefinition($workflow)
                ->setWorkflowTransitionName($transitionName);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return object|WorkflowDefinition
     */
    private function getWorkflowDefinition(ObjectManager $manager)
    {
        $workflow = $manager->getRepository(WorkflowDefinition::class)->findOneBy(['name' => self::WORKFLOW_NAME]);

        if (!$workflow) {
            throw new \RuntimeException(sprintf('Required workflow definition "%s" not found', self::WORKFLOW_NAME));
        }

        return $workflow;
    }

    /**
     * @param ObjectManager $manager
     * @param string $templateName
     *
     * @return object|EmailTemplate
     */
    private function getEmailTemplate(ObjectManager $manager, $templateName)
    {
        $template = $manager->getRepository(EmailTemplate::class)->findOneBy(['name' => $templateName]);

        if (!$template) {
            throw new \RuntimeException(sprintf('Required email template "%s" not found', $templateName));
        }

        return $template;
    }
}
