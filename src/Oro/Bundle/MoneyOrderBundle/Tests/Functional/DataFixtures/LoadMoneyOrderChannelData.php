<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadMoneyOrderChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var array Channels configuration
     */
    protected $channelData = [
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

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $organization = $manager->getRepository(Organization::class)->getFirst();

        foreach ($this->channelData as $data) {
            $entity = new Channel();
            /** @var Transport $transport */
            $transportId = $this->getReference($data['transport'])->getId();
            $transport = $manager->getRepository(MoneyOrderSettings::class)->findOneBy(['id' => $transportId]);
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setEnabled($data['enabled']);
            $entity->setDefaultUserOwner($admin);
            $entity->setOrganization($organization);
            $entity->setTransport($transport);
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\LoadMoneyOrderSettingsData'
        ];
    }
}
