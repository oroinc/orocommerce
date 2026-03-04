<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets the Accessibility landing page as a default value
 * for the system configuration option {@see Configuration::ACCESSIBILITY_PAGE}.
 *
 * The page itself is loaded by {@see LoadPageData} from pages.yml under the 'accessibility' reference.
 */
class LoadAccessibilityPageData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadPageData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $this->setAccessibilityPageSystemConfiguration();
    }

    private function setAccessibilityPageSystemConfiguration(): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::ACCESSIBILITY_PAGE),
            $this->getReference('accessibility')->getId()
        );
        $configManager->flush();
    }
}
