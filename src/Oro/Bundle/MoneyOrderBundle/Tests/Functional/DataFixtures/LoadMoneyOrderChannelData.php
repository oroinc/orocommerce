<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadMoneyOrderChannelData extends AbstractFixture implements DependentFixtureInterface
{
    private array $channelData = [
        [
            'name' => 'MoneyOrder1',
            'type' => 'money_order',
            'enabled' => true,
            'transport' => 'money_order:transport_1',
            'reference' => 'money_order:channel_1',
        ],
        [
            'name' => 'MoneyOrder2',
            'type' => 'money_order',
            'enabled' => true,
            'transport' => 'money_order:transport_2',
            'reference' => 'money_order:channel_2',
        ],
        [
            'name' => 'MoneyOrder3',
            'type' => 'money_order',
            'enabled' => false,
            'transport' => 'money_order:transport_3',
            'reference' => 'money_order:channel_3',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadMoneyOrderSettingsData::class, LoadOrganization::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->channelData as $data) {
            $entity = new Channel();
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setEnabled($data['enabled']);
            $entity->setDefaultUserOwner($this->getReference(LoadUser::USER));
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setTransport($this->getReference($data['transport']));
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
