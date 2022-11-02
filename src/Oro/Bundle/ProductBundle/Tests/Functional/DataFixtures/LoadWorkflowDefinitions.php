<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

class LoadWorkflowDefinitions extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $workflowConfiguration = $this->container
            ->get('oro_workflow.configuration.config.workflow_list')
            ->processConfiguration($this->getConfiguration());

        $workflowDefinitions = $this->container
            ->get('oro_workflow.configuration.builder.workflow_definition')
            ->buildFromConfiguration($workflowConfiguration);

        foreach ($workflowDefinitions as $workflowDefinition) {
            $manager->persist($workflowDefinition);
        }

        $manager->flush();

        $cache = $this->container->get('oro_workflow.cache.entity_aware');
        $cache->invalidateActiveRelated();
    }

    private function getConfiguration(): array
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/workflows_fixture.yml'));
    }
}
