<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\DependencyInjection\Configuration;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads tax configuration.
 */
class LoadTaxConfigurationDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    private static $configurations = [
        'origin_address' => [
            'country' => 'US',
            'region' => 'US-CA', #California
            'region_text' => null,
            'postal_code' => '90401' #Santa Monica
        ],
        'use_as_base_by_default' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadTaxTableRatesDemoData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $configManager = $this->container->get('oro_config.global');

        foreach (self::$configurations as $option => $value) {
            $configManager->set(
                Configuration::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $option,
                $value
            );
        }

        $configManager->flush();
    }
}
