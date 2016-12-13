<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class UpdateAlternativeCheckoutWorkflowStatus extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadAccountDemoData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition')
            ->find('b2b_flow_alternative_checkout');

        if (!$workflowDefinition) {
            return;
        }

        $this->container->get('oro_workflow.manager')->activateWorkflow($workflowDefinition->getName());
    }
}
