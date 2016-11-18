<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class DisableAlternativeCheckoutByDefault extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition')
            ->find('b2b_flow_alternative_checkout');

        if ($workflowDefinition && $workflowDefinition->isActive()) {
            $this->container->get('oro_workflow.manager')->deactivateWorkflow($workflowDefinition->getName());
        }
    }
}
