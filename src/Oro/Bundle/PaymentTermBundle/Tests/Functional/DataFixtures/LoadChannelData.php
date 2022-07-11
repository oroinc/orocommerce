<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const PAYMENT_TERM_INTEGRATION_CHANNEL = 'payment_term:channel_1';

    /**
     * @var array Channels configuration
     */
    public static $channelData = [
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

    /**
     * @var ContainerInterface
     */
    protected $container;

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
        $admin = $this->getUser($userManager);
        $organization = $admin->getOrganization();

        foreach (self::$channelData as $data) {
            $entity = new Channel();
            /** @var Transport $transport */
            $transportId = $this->getReference($data['transport'])->getId();
            $transport = $manager->getRepository(PaymentTermSettings::class)->findOneBy(['id' => $transportId]);
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setOrganization($organization);
            $entity->setTransport($transport);
            $entity->setEnabled($data['enabled']);
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
        return [
            LoadPaymentTermSettingsData::class
        ];
    }

    /**
     * @param UserManager $userManager
     *
     * @return User|UserInterface
     */
    protected function getUser(UserManager $userManager)
    {
        $user = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}
