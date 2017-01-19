<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var array Channels configuration
     */
    public static $channelData = [
        [
            'name' => 'Payment Term 1',
            'type' => 'payment_term',
            'transport' => 'payment_term:transport_1',
            'enabled' => true,
            'reference' => 'payment_term:channel_1',
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

        foreach (self::$channelData as $data) {
            $entity = new Channel();
            /** @var Transport $transport */
            $transportId = $this->getReference($data['transport'])->getId();
            $transport = $manager
                ->getRepository('OroPaymentTermBundle:PaymentTermSettings')
                ->findOneBy(['id' => $transportId]);
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setDefaultUserOwner($admin);
            $entity->setOrganization($organization);
            $entity->setTransport($transport);
            $entity->setEnabled($data['enabled']);
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
            __NAMESPACE__ . '\LoadPaymentTermSettingsData'
        ];
    }
}
