<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadAlternativeCheckoutScopesData extends AbstractFixture implements
    FixtureInterface,
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAccountDemoData::class,
        ];
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

        /** @var Account $account */
        $account = $manager->getRepository('OroCustomerBundle:Account')
            ->findOneBy(['name' => LoadAccountDemoData::ACCOUNT_WHOLESALLER_B]);

        if (!$account) {
            return;
        }

        $workflowDefinition->setScopesConfig(
            array_merge(
                $workflowDefinition->getScopesConfig(),
                [
                    [ScopeAccountCriteriaProvider::ACCOUNT => $account->getId()]
                ]
            )
        );

        $manager->flush();

        if (!$workflowDefinition->isActive()) {
            $this->container->get('oro_workflow.manager')->activateWorkflow($workflowDefinition->getName());
        }

        $this->container->get('oro_workflow.manager.workflow_scope')->updateScopes($workflowDefinition);
    }
}
