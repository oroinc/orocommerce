<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    private array $channelData = [
        [
            'name' => 'UPS1',
            'type' => 'ups',
            'transport' => 'ups:transport_1',
            'enabled' => true,
            'reference' => 'ups:channel_1',
        ],
        [
            'name' => 'UPS2',
            'type' => 'ups',
            'transport' => 'ups:transport_2',
            'enabled' => true,
            'reference' => 'ups:channel_2',
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadTransportData::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        foreach ($this->channelData as $data) {
            $entity = new Channel();
            /** @var Transport $transport */
            $transport = $this->getReference($data['transport']);
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setDefaultUserOwner($user);
            $entity->setOrganization($user->getOrganization());
            $entity->setTransport($transport);
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }
        $manager->flush();
    }
}
