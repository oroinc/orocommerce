<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPayPalChannelData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var array Channels configuration
     */
    protected $channelData = [
        [
            'name' => 'PayPal1',
            'type' => 'paypal_payflow_gateway',
            'enabled' => true,
            'reference' => 'paypal:channel_1',
        ],
        [
            'name' => 'PayPal2',
            'type' => 'paypal_payflow_gateway',
            'enabled' => false,
            'reference' => 'paypal:channel_2',
        ],
        [
            'name' => 'PayPal3',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
            'reference' => 'paypal:channel_3',
        ],
        [
            'name' => 'PayPal4',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
            'reference' => 'paypal:channel_4',
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
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($this->channelData as $data) {
            $entity = new Channel();
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setEnabled($data['enabled']);
            $entity->setDefaultUserOwner($admin);
            $entity->setOrganization($organization);
            $entity->setTransport(new PayPalSettings());
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }
        $manager->flush();
    }
}
