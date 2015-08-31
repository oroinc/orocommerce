<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as DoctrineAbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

abstract class AbstractFixture extends DoctrineAbstractFixture implements ContainerAwareInterface
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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
        ];
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        $role = $manager->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        /** @var RoleRepository $roleRepository */
        $roleRepository = $manager->getRepository('OroUserBundle:Role');
        $user = $roleRepository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
