<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadPayPalChannelData extends AbstractFixture implements DependentFixtureInterface
{
    public const PAYPAL_PAYFLOW_GATAWAY1 = 'paypal:channel_1';
    public const PAYPAL_PAYFLOW_GATAWAY2 = 'paypal:channel_2';
    public const PAYPAL_PAYMENTS_PRO1 = 'paypal:channel_3';
    public const PAYPAL_PAYMENTS_PRO2 = 'paypal:channel_4';

    private array $channelData = [
        self::PAYPAL_PAYFLOW_GATAWAY1 => [
            'name' => 'PayPal1',
            'type' => 'paypal_payflow_gateway',
            'enabled' => true,
        ],
        self::PAYPAL_PAYFLOW_GATAWAY2 => [
            'name' => 'PayPal2',
            'type' => 'paypal_payflow_gateway',
            'enabled' => false,
        ],
        self::PAYPAL_PAYMENTS_PRO1 => [
            'name' => 'PayPal3',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
        ],
        self::PAYPAL_PAYMENTS_PRO2 => [
            'name' => 'PayPal4',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->channelData as $reference => $data) {
            $entity = new Channel();
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setEnabled($data['enabled']);
            $entity->setDefaultUserOwner($this->getReference(LoadUser::USER));
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setTransport(new PayPalSettings());
            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
