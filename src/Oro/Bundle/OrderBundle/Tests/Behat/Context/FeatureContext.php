<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager as FeatureConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * This context method can change order createdAt field,so we can tests time related features
     *
     * Example: Given there is an order "OldOrder" created "-15 days"
     *
     * @Given /^there is an order "(?P<orderIdentifier>(?:[^"]|\\")*)" created "(?P<createdAt>(?:[^"]|\\")*)"$/
     */
    public function thereAnOrderCreatedAt($orderIdentifier, $createdAt)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()
            ->get('oro_entity.doctrine_helper')->getEntityManager(Order::class);

        $order = $em->getRepository(Order::class)
            ->findOneBy(['identifier' => $orderIdentifier]);

        /** @var Order $order */
        if ($order) {
            // Because after loading fixtures not all fields is up-to-date
            $em->refresh($order);

            $order->setCreatedAt(new \DateTime($createdAt));
            $em->persist($order);
            $em->flush();
        } else {
            throw new EntityNotFoundException(sprintf('Order with identifier "%s" not found', $orderIdentifier));
        }
    }

    /**
     * This context method ensure that particular feature is enabled and cleanup feature status cache (by event)
     *
     * Example: Given there is a feature "previously_purchased_products" enabled
     *
     * @Given /^there is a feature "(?P<feature>(?:[^"]|\\")*)" enabled$/
     */
    public function thereEnabledFeature($feature)
    {
        /** @var FeatureConfigurationManager $featureConfigManager */
        $featureConfigManager = $this->getContainer()->get('oro_featuretoggle.configuration.manager');
        $toggleConfigOption = $featureConfigManager->get($feature, ConfigVoter::TOGGLE_KEY);

        /* @var $configManager ConfigManager */
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set($toggleConfigOption, true);
        $configManager->flush();
    }
}
