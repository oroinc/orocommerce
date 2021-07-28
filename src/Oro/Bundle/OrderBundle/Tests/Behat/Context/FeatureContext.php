<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager as FeatureConfigurationManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;

class FeatureContext extends OroFeatureContext implements FixtureLoaderAwareInterface
{
    use FixtureLoaderDictionary;

    private ManagerRegistry $managerRegistry;

    private ConfigManager $configManager;

    private DoctrineHelper $doctrineHelper;

    private FeatureConfigurationManager $featureConfigManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        FeatureConfigurationManager $featureConfigManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->featureConfigManager = $featureConfigManager;
    }

    /**
     * Load "BestSelling.yml" alice fixture from OrderBundle suite
     *
     * PrePersist lifecycleCallback will override createdAt and updatedAt fields passed from fixture.
     * So, we should disable this callback to save original values.
     *
     * @Given /^best selling fixture loaded$/
     */
    public function bestSellingFixtureLoaded()
    {
        $metadata = $this->getMetadata();

        $events = $metadata->lifecycleCallbacks;
        $metadata->setLifecycleCallbacks([]);

        $this->fixtureLoader->loadFixtureFile('OroOrderBundle:BestSelling.yml');

        $metadata->setLifecycleCallbacks($events);
    }

    /**
     * @return ClassMetadataInfo
     */
    private function getMetadata()
    {
        $manager = $this->managerRegistry->getManagerForClass(Order::class);

        return $manager->getClassMetadata(Order::class);
    }

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
        $em = $this->doctrineHelper->getEntityManager(Order::class);

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
        $toggleConfigOption = $this->featureConfigManager->get($feature, ConfigVoter::TOGGLE_KEY);

        $this->configManager->set($toggleConfigOption, true);
        $this->configManager->flush();
    }
}
