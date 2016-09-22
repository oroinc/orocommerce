<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var array Channels configuration
     */
    protected $channelData = [
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
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($this->channelData as $data) {
            $entity = new Channel();
            /** @var Transport $transport */
            $transportId = $this->getReference($data['transport'])->getId();
            $transport = $manager
                ->getRepository('OroUPSBundle:UPSTransport')
                ->findOneBy(['id' => $transportId]);
            $entity->setName($data['name']);
            $entity->setType($data['type']);
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
            __NAMESPACE__ . '\LoadTransportData'
        ];
    }
}
