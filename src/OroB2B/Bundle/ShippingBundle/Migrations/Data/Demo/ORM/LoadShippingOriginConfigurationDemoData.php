<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\OroB2BShippingExtension;

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
                OroB2BShippingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $option,
                $value
            );
        }

        $configManager->flush();
    }
}
