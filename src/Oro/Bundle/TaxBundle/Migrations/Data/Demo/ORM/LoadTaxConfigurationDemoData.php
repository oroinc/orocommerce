<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class LoadTaxConfigurationDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM\LoadTaxTableRatesDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.global');

        foreach (self::$configurations as $option => $value) {
            $configManager->set(
                OroTaxExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $option,
                $value
            );
        }

        $configManager->flush();
    }
}
