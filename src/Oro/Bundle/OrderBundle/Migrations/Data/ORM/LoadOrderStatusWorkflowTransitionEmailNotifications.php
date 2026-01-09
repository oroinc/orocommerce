<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads email notifications rules for "order_processing_flow" workflow.
 */
class LoadOrderStatusWorkflowTransitionEmailNotifications extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const WORKFLOW_NAME = 'order_processing_flow';
    private const NOTIFICATIONS = [
        'order_processing_flow_confirmed' => 'confirm',
        'order_processing_flow_processing' => 'process',
        'order_processing_flow_marked_as_shipped' => 'mark_as_shipped',
        'order_processing_flow_completed' => 'complete',
        'order_processing_flow_declined' => 'decline'
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrderStatusWorkflowEmailTemplates::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $workflow = $this->getWorkflowDefinition($manager);
        foreach (self::NOTIFICATIONS as $emailTemplateName => $transitionName) {
            $emailTemplate = $this->getEmailTemplate($manager, $emailTemplateName);

            $recipientList = new RecipientList();
            $recipientList->setAdditionalEmailAssociations(['customerUser']);
            $manager->persist($recipientList);

            $entity = new EmailNotification();
            $entity->setEntityName(Order::class);
            $entity->setEventName(WorkflowEvents::NOTIFICATION_TRANSIT_EVENT);
            $entity->setTemplate($emailTemplate);
            $entity->setRecipientList($recipientList);
            $entity->setWorkflowDefinition($workflow);
            $entity->setWorkflowTransitionName($transitionName);
            $manager->persist($entity);
        }
        $manager->flush();
    }

    private function getWorkflowDefinition(ObjectManager $manager): WorkflowDefinition
    {
        $workflow = $manager->getRepository(WorkflowDefinition::class)->findOneBy(['name' => self::WORKFLOW_NAME]);
        if (null === $workflow) {
            throw new \RuntimeException(sprintf('The workflow definition "%s" was not found.', self::WORKFLOW_NAME));
        }

        return $workflow;
    }

    private function getEmailTemplate(ObjectManager $manager, string $templateName): EmailTemplate
    {
        $template = $manager->getRepository(EmailTemplate::class)->findOneBy(['name' => $templateName]);
        if (null === $template) {
            throw new \RuntimeException(sprintf('The email template "%s" was not found.', $templateName));
        }

        return $template;
    }
}
