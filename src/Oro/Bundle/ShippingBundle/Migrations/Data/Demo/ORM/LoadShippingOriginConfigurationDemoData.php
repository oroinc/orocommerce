<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;

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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.scope.global');

        foreach (self::$configurations as $option => $value) {
            $configManager->set(
                OroShippingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $option,
                $value
            );
        }

        $configManager->flush();
    }
}
