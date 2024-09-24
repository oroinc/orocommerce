<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    #[\Override]
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $this->setWorkflowsReference($doctrine, $referenceRepository);
        $this->setInternalStatusesReference($doctrine, $referenceRepository);
        $this->setCustomerStatusesReference($doctrine, $referenceRepository);
    }

    private function setWorkflowsReference(ManagerRegistry $doctrine, Collection $referenceRepository): void
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

    private function setInternalStatusesReference(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManager()->getRepository(EnumOption::class);
        /** @var EnumOptionInterface $status */
        foreach ($repository->findBy(['enumCode' => Quote::INTERNAL_STATUS_CODE]) as $status) {
            $referenceRepository->set(
                \sprintf('%s_%s', Quote::INTERNAL_STATUS_CODE, $status->getInternalId()),
                $status
            );
        }
    }

    private function setCustomerStatusesReference(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManager()->getRepository(EnumOption::class);
        /** @var EnumOptionInterface $status */
        foreach ($repository->findBy(['enumCode' => Quote::CUSTOMER_STATUS_CODE]) as $status) {
            $referenceRepository->set(
                \sprintf('%s_%s', Quote::CUSTOMER_STATUS_CODE, $status->getInternalId()),
                $status
            );
        }
    }
}
