<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Migrations\Data\Demo\ORM\AbstractLoadEntityWorkflowDemoData;
use Oro\Bundle\WorkflowBundle\Model\Transition;

/**
 * Loads demo data for workflow steps.
 */
class LoadRequestWorkflowDemoData extends AbstractLoadEntityWorkflowDemoData
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadRequestDemoData::class
        ];
    }

    #[\Override]
    protected function getWorkflows()
    {
        return [
            'b2b_rfq_backoffice_default',
            'b2b_rfq_frontoffice_default',
        ];
    }

    #[\Override]
    protected function getIgnoredTransitions()
    {
        return [
            'b2b_rfq_frontoffice_default' => [
                'resubmit_transition_definition',
            ],
        ];
    }

    #[\Override]
    protected function getDeepLevel()
    {
        return 4;
    }

    #[\Override]
    protected function getEntityUser($request, $workflowName)
    {
        /* @var $request Request */
        return $workflowName === 'b2b_rfq_frontoffice_default' ? $request->getCustomerUser() : $request->getOwner();
    }

    #[\Override]
    protected function getEntities(ObjectManager $manager)
    {
        return $manager->getRepository(Request::class)->findAll();
    }

    #[\Override]
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

    #[\Override]
    protected function generateTransitionsHistory($workflowName, array $entities)
    {
        /* @var ChainOwnershipMetadataProvider $chainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        parent::generateTransitionsHistory($workflowName, $entities);

        $chainMetadataProvider->stopProviderEmulation();
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
