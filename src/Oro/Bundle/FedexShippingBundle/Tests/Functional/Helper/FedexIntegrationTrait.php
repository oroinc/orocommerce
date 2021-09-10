<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Functional\Helper;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Tests\Functional\Helper\AdminUserTrait;

trait FedexIntegrationTrait
{
    use AdminUserTrait;

    protected function createFedexIntegrationSettings(bool $enabled = true)
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

        $services = static::getContainer()
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
