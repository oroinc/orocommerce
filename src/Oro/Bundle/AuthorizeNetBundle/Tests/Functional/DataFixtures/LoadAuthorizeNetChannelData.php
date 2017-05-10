<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadAuthorizeNetChannelData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var array Channels configuration
     */
    protected $channelData = [
        [
            'name' => 'AuthorizeNet',
            'type' => 'authorize_net',
            'enabled' => true,
            'reference' => 'authorize_net:channel_1',
        ],
        [
            'name' => 'AuthorizeNet2',
            'type' => 'authorize_net',
            'enabled' => true,
            'reference' => 'authorize_net:channel_2',
        ],
        [
            'name' => 'AuthorizeNet3',
            'type' => 'authorize_net',
            'enabled' => false,
            'reference' => 'authorize_net:channel_3',
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
            $entity->setTransport(new AuthorizeNetSettings());
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }
        $manager->flush();
    }
}
