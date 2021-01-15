<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Migrations\Data\Demo\ORM\AbstractLoadEntityWorkflowDemoData;
use Oro\Bundle\WorkflowBundle\Model\Transition;

/**
 * Adds workflow demo data for the quote entity.
 */
class LoadQuoteWorkflowDemoData extends AbstractLoadEntityWorkflowDemoData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadQuoteDemoData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $helper = $this->container->get('oro_sale.helper.notification');
        $helper->setEnabled(false);

        $notificationListener = $this->container->get('oro_workflow.listener.workflow_transition_record');
        $notificationListener->setEnabled(false);

        parent::load($manager);

        $notificationListener->setEnabled(true);
        $helper->setEnabled(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getWorkflows()
    {
        return [
            'b2b_quote_backoffice_approvals',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIgnoredTransitions()
    {
        return [
            'b2b_quote_backoffice_approvals' => [
                'edit_transition',
                'clone_transition',
                'create_new_quote_transition',
                'reopen_transition',
                'undelete_transition',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeepLevel()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityUser($quote, $workflowName)
    {
        /* @var $quote Quote */
        return $quote->getOwner();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(ObjectManager $manager)
    {
        return $manager->getRepository(Quote::class)->findAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function transitWorkflow(WorkflowItem $workflowItem, Transition $transition)
    {
        /** @var Quote $quote */
        $quote = $workflowItem->getEntity();

        switch ($transition->getName()) {
            case 'send_to_customer_transition':
                /* @var $email Email */
                $email = $this->container->get('oro_sale.helper.notification')->getEmailModel($quote);
                $email->setFrom($quote->getOwner()->getEmail())
                    ->setTo([
                        $quote->getOwner()->getEmail()
                    ]);
                $workflowItem->getData()->set('email', $email);

                break;
        }

        parent::transitWorkflow($workflowItem, $transition);
    }
}
