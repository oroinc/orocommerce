<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\DependencyInjection\Configuration;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads configuration for shipping origins.
 */
class LoadShippingOriginConfigurationDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    private static $configurations = [
        'shipping_origin' => [
            'country' => 'US',
            'region' => 'US-CA',
            'region_text' => null,
            'postalCode' => '90401',
            'city' => 'Santa Monica',
            'street' => '1685 Main St.',
            'street2' => null
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager)
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
