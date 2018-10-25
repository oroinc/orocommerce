<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteInternalStatuses;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $this->setWorkflowsReference($doctrine, $referenceRepository);
        $this->setInternalStatusesReference($doctrine, $referenceRepository);
    }

    /**
     * @param Registry $doctrine
     * @param AliceCollection $referenceRepository
     */
    private function setWorkflowsReference(Registry $doctrine, AliceCollection $referenceRepository): void
    {
        $workflowDefinitionRepo = $doctrine->getManager()->getRepository(WorkflowDefinition::class);
        $workflowName = 'b2b_quote_backoffice_approvals';
        $workflowDefinition = $workflowDefinitionRepo->findOneBy(['name' => $workflowName]);
        $referenceRepository->set(sprintf('workflow_%s', $workflowName), $workflowDefinition);

        $workflowStepRepo = $doctrine->getManager()->getRepository(WorkflowStep::class);
        $workflowSteps = $workflowStepRepo->findBy(['definition' => $workflowDefinition]);
        foreach ($workflowSteps as $workflowStep) {
            $referenceRepository->set(
                sprintf('workflow_%s_%s', $workflowName, $workflowStep->getName()),
                $workflowStep
            );
        }
    }

    /**
     * @param Registry $doctrine
     * @param AliceCollection $referenceRepository
     */
    private function setInternalStatusesReference(Registry $doctrine, AliceCollection $referenceRepository): void
    {
        $enumClass = ExtendHelper::buildEnumValueClassName(Quote::INTERNAL_STATUS_CODE);

        foreach (LoadQuoteInternalStatuses::getDataKeys() as $status) {
            $referenceRepository->set(
                sprintf('quote_internal_status_%s', $status),
                $doctrine->getManagerForClass($enumClass)->getReference($enumClass, $status)
            );
        }
    }
}
