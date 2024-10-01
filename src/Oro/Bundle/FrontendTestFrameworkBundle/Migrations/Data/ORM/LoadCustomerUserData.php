<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\LoadUserData;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Creates the customer user entity.
 */
class LoadCustomerUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const AUTH_USER = 'customer_user@example.com';
    public const AUTH_PW = 'customer_user';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
            LoadCustomerUserRoles::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var BaseUserManager $userManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $role = $manager->getRepository(CustomerUserRole::class)
            ->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);

        /** @var CustomerUser $entity */
        $entity = $userManager->createUser();
        $entity
            ->setFirstName('CustomerUser')
            ->setLastName('CustomerUser')
            ->setEmail(self::AUTH_USER)
            ->setOwner($manager->getRepository(User::class)->findOneBy([]))
            ->setEnabled(true)
            ->setSalt('')
            ->setPlainPassword(self::AUTH_PW)
            ->setOrganization($manager->getRepository(Organization::class)->getFirst())
            ->addUserRole($role);

        $userManager->updateUser($entity);
    }
}
