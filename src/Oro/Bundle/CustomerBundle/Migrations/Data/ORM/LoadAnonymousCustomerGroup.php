<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadAnonymousCustomerGroup extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var string
     */
    const GROUP_NAME_NON_AUTHENTICATED = 'Non-Authenticated Visitors';

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
        $customerGroup = new CustomerGroup();
        $customerGroup->setName(self::GROUP_NAME_NON_AUTHENTICATED);

        /** @var EntityManager $manager */
        $manager->persist($customerGroup);
        $manager->flush($customerGroup);

        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_customer.anonymous_customer_group', $customerGroup->getId());
        $configManager->flush();
    }
}
