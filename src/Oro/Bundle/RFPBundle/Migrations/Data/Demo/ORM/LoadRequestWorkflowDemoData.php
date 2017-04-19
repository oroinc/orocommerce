<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Migrations\Data\Demo\ORM\AbstractLoadEntityWorkflowDemoData;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class LoadRequestWorkflowDemoData extends AbstractLoadEntityWorkflowDemoData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadRequestDemoData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getWorkflows()
    {
        return [
            'b2b_rfq_backoffice_default',
            'b2b_rfq_frontoffice_default',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIgnoredTransitions()
    {
        return [
            'b2b_rfq_frontoffice_default' => [
                'resubmit_transition_definition',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeepLevel()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityUser($request, $workflowName)
    {
        /* @var $request Request */
        return $workflowName === 'b2b_rfq_frontoffice_default' ? $request->getCustomerUser() : $request->getOwner();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(ObjectManager $manager)
    {
        return $manager->getRepository(Request::class)->findAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function transitWorkflow(WorkflowItem $workflowItem, Transition $transition)
    {
        switch ($transition->getName()) {
            case 'provide_more_information_transition':
            case 'request_more_information_transition':
                $workflowItem->getData()->set('notes', $this->getNote());

                break;
        }

        parent::transitWorkflow($workflowItem, $transition);
    }

    /**
     * @return string
     */
    private function getNote()
    {
        return 'Aliquam quis turpis eget elit sodales scelerisque.' .
            'Mauris sit amet eros. Suspendisse accumsan tortor quis turpis.' .
            'Sed ante. Vivamus tortor. Duis mattis egestas metus.';
    }
}
