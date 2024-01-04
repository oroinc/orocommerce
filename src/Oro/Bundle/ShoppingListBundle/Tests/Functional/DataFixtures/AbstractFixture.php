<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as DoctrineAbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractFixture extends DoctrineAbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadAdminUserData::class];
    }

    protected function getUser(ObjectManager $manager): User
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL]);
        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }
}
