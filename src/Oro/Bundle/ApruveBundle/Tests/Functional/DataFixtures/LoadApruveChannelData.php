<?php

namespace Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadApruveChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var array Channels configuration
     */
    const CHANNEL_DATA = [
        [
            'name' => 'Apruve1',
            'type' => 'apruve',
            'enabled' => true,
            'transport' => 'apruve:transport_1',
            'reference' => 'apruve:channel_1',
        ],
        [
            'name' => 'Apruve2',
            'type' => 'apruve',
            'enabled' => true,
            'transport' => 'apruve:transport_2',
            'reference' => 'apruve:channel_2',
        ],
        [
            'name' => 'Apruve3',
            'type' => 'apruve',
            'enabled' => false,
            'transport' => 'apruve:transport_3',
            'reference' => 'apruve:channel_3',
        ],
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach (self::CHANNEL_DATA as $data) {
            $entity = new Channel();
            /** @var Transport $transport */
            $transportId = $this->getReference($data['transport'])->getId();
            $transport = $manager
                ->getRepository(ApruveSettings::class)
                ->findOneBy(['id' => $transportId]);
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
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadApruveSettingsData::class];
    }
}
