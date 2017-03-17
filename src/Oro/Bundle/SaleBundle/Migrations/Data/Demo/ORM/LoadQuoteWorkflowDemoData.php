<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Migrations\Data\Demo\ORM\AbstractLoadEntityWorkflowDemoData;
use Oro\Bundle\WorkflowBundle\Model\Transition;

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
        $transport = $this->container->get('swiftmailer.mailer.default.transport.real');
        $this->container->set(
            'swiftmailer.mailer.default.transport.real',
            new \Swift_Transport_NullTransport(new \Swift_Events_SimpleEventDispatcher())
        );

        parent::load($manager);

        $this->container->set('swiftmailer.mailer.default.transport.real', $transport);
    }

    /**
     * {@inheritdoc}
     */
    protected function getWorkflows()
    {
        return [
            'b2b_quote_backoffice_default',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIgnoredTransitions()
    {
        return [
            'b2b_quote_backoffice_default' => [
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
