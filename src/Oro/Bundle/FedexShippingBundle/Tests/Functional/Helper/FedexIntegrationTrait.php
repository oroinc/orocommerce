<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait FedexIntegrationTrait
{
    /**
     * @return ContainerInterface
     */
    abstract public static function getContainer();

    protected function createFedexIntegrationSettings(bool $enabled = true)
    {
        /** @var User $admin */
        $admin = static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroUserBundle:User')
            ->findOneBy(['email' => WebTestCase::AUTH_USER]);

        $settings = new FedexIntegrationSettings();
        $settings
            ->setKey('key')
            ->setPassword('pass')
            ->setAccountNumber('number')
            ->setMeterNumber('meter')
            ->setFedexTestMode(true)
            ->setPickupType(FedexIntegrationSettings::PICKUP_TYPE_DROP_BOX)
            ->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $services = static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroFedexShippingBundle:ShippingService')
            ->findAll();
        foreach ($services as $service) {
            $settings->addShippingService($service);
        }

        $channel = new Channel();
        $channel
            ->setName('fedex')
            ->setType('fedex')
            ->setEnabled($enabled)
            ->setDefaultUserOwner($admin)
            ->setOrganization($admin->getOrganization())
            ->setTransport($settings);

        static::getContainer()->get('doctrine')->getManager()->persist($channel);
        static::getContainer()->get('doctrine')->getManager()->flush();
    }

    /**
     * @return FedexIntegrationSettings|null
     */
    protected function getFedexIntegrationSettings()
    {
        $settings = static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroFedexShippingBundle:FedexIntegrationSettings')
            ->findAll();

        if (empty($settings)) {
            return null;
        }

        return $settings[0];
    }
}
