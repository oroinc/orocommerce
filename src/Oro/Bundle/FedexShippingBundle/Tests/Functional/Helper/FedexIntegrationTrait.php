<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User;

trait FedexIntegrationTrait
{
    private function getAdminUser(): User
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);
    }

    private function createFedexIntegrationSettings(bool $enabled = true): void
    {
        $admin = $this->getAdminUser();

        $settings = new FedexIntegrationSettings();
        $settings
            ->setKey('key')
            ->setPassword('pass')
            ->setAccountNumber('number')
            ->setMeterNumber('meter')
            ->setFedexTestMode(true)
            ->setPickupType(FedexIntegrationSettings::PICKUP_TYPE_DROP_BOX)
            ->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        $services = self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(FedexShippingService::class)
            ->findBy([
                'code' => [
                    'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
                    'FEDEX_1_DAY_FREIGHT',
                    'FEDEX_2_DAY',
                    'FEDEX_2_DAY_AM',
                    'FEDEX_2_DAY_FREIGHT',
                ]
            ]);
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

        self::getContainer()->get('doctrine')->getManager()->persist($channel);
        self::getContainer()->get('doctrine')->getManager()->flush();
    }

    private function getFedexIntegrationSettings(): ?FedexIntegrationSettings
    {
        $settings = self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(FedexIntegrationSettings::class)
            ->findAll();

        if (empty($settings)) {
            return null;
        }

        return $settings[0];
    }
}
