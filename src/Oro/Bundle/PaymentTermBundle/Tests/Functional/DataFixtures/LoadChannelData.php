<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    public const PAYMENT_TERM_INTEGRATION_CHANNEL = 'payment_term:channel_1';

    private static array $channelData = [
        [
            'name' => 'Payment Term 1',
            'type' => 'payment_term',
            'transport' => 'payment_term:transport_1',
            'enabled' => true,
            'reference' => self::PAYMENT_TERM_INTEGRATION_CHANNEL,
        ],
        [
            'name' => 'Payment Term 2',
            'type' => 'payment_term',
            'transport' => 'payment_term:transport_2',
            'enabled' => true,
            'reference' => 'payment_term:channel_2',
        ],
        [
            'name' => 'Payment Term 3',
            'type' => 'payment_term',
            'transport' => 'payment_term:transport_3',
            'enabled' => false,
            'reference' => 'payment_term:channel_3',
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadPaymentTermSettingsData::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        foreach (self::$channelData as $data) {
            $entity = new Channel();
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setOrganization($user->getOrganization());
            $entity->setTransport($this->getReference($data['transport']));
            $entity->setEnabled($data['enabled']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
