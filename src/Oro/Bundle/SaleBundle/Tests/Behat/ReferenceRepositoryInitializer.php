<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
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
        $enumClass = ExtendHelper::buildEnumValueClassName(Quote::INTERNAL_STATUS_CODE);
        $repository = $doctrine->getManager()->getRepository($enumClass);
        /** @var AbstractEnumValue $status */
        foreach ($repository->findAll() as $status) {
            $referenceRepository->set(sprintf('quote_internal_status_%s', $status->getId()), $status);
        }
    }

    private function setCustomerStatusesReference(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $enumClass = ExtendHelper::buildEnumValueClassName(Quote::CUSTOMER_STATUS_CODE);
        $repository = $doctrine->getManager()->getRepository($enumClass);
        /** @var AbstractEnumValue $status */
        foreach ($repository->findAll() as $status) {
            $referenceRepository->set(sprintf('quote_customer_status_%s', $status->getId()), $status);
        }
    }
}
