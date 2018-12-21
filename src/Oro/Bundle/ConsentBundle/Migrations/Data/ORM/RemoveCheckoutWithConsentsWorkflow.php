<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Removes old `b2b_flow_checkout_with_consents` workflow
 * and enables consents system configuration to true if it was active
 */
class RemoveCheckoutWithConsentsWorkflow extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $checkoutWithConsentsWorkflow =
            $manager->find(WorkflowDefinition::class, 'b2b_flow_checkout_with_consents');

        if (!$checkoutWithConsentsWorkflow) {
            return;
        }

        //Force set consents configuration to true if checkout with consents was enabled
        if ($checkoutWithConsentsWorkflow->isActive()) {
            $configManager = $this->container->get('oro_config.global');
            $configManager->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), true);

            $configManager->flush();
        }

        //WorkflowDefinitionEntityListener prevents removing system definitions so we need to overcome this check
        $checkoutWithConsentsWorkflow->setSystem(false);
        $manager->remove($checkoutWithConsentsWorkflow);
        $manager->flush();
    }
}
