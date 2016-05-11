<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class LoadAnonymousAccountGroup extends AbstractFixture implements ContainerAwareInterface
{
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
        $accountGroup = new AccountGroup();
        $accountGroup->setName('Non-Authenticated Visitors');

        /** @var EntityManager $manager */
        $manager->persist($accountGroup);
        $manager->flush($accountGroup);

        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_b2b_account.anonymous_account_group', $accountGroup->getId());
        $configManager->flush();
    }
}
