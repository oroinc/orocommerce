<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\UserBundle\Entity\Role;

abstract class AbstractLoadCustomerUserFixture extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /** @return array */
    abstract protected function getCustomers();

    /** @return array */
    abstract protected function getRoles();

    /** @return array */
    abstract protected function getCustomerUsers();

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
        $this->loadRoles($manager);
        $this->loadCustomers($manager);
        $this->loadCustomerUsers($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadCustomers(ObjectManager $manager)
    {
        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->getCustomers() as $item) {
            $customer = new Customer();
            $customer
                ->setName($item['name'])
                ->setOrganization($organization)
                ->setOwner($defaultUser);
            if (isset($item['parent'])) {
                $customer->setParent($this->getReference($item['parent']));
            }
            $manager->persist($customer);

            $this->addReference($item['name'], $customer);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadRoles(ObjectManager $manager)
    {
        /* @var $aclManager AclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        foreach ($this->getRoles() as $key => $items) {
            $role = new CustomerUserRole(CustomerUserRole::PREFIX_ROLE . $key);
            $role->setLabel($key);

            foreach ($items as $acls) {
                $className = $this->container->getParameter($acls['class']);

                $this->setRolePermissions($aclManager, $role, $className, $acls['acls']);
            }

            $manager->persist($role);

            $this->setReference($key, $role);
        }

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadCustomerUsers(ObjectManager $manager)
    {
        /* @var $userManager CustomerUserManager */
        $userManager = $this->container->get('oro_customer_user.manager');

        $defaultUser    = $this->getUser($manager);
        $organization   = $defaultUser->getOrganization();

        foreach ($this->getCustomerUsers() as $item) {
            /* @var $customerUser CustomerUser */
            $customerUser = $userManager->createUser();

            $customerUser
                ->setEmail($item['email'])
                ->setCustomer($this->getReference($item['customer']))
                ->setOwner($defaultUser)
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setConfirmed(true)
                ->setOrganization($organization)
                ->addRole($this->getReference($item['role']))
                ->setSalt('')
                ->setPlainPassword($item['password'])
                ->setEnabled(true)
            ;

            $userManager->updateUser($customerUser);

            $this->setReference($item['email'], $customerUser);
        }
    }

    /**
     * @param AclManager $aclManager
     * @param CustomerUserRole $role
     * @param string $className
     * @param array $allowedAcls
     */
    protected function setRolePermissions(
        AclManager $aclManager,
        CustomerUserRole $role,
        $className,
        array $allowedAcls
    ) {
        /* @var $chainMetadataProvider ChainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);

            foreach ($aclManager->getAllExtensions() as $extension) {
                if ($extension instanceof EntityAclExtension) {
                    $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
                    $oid = $aclManager->getOid('entity:' . $className);
                    $builder = $aclManager->getMaskBuilder($oid);
                    $mask = $builder->reset()->get();
                    foreach ($allowedAcls as $acl) {
                        $mask = $builder->add($acl)->get();
                    }
                    $aclManager->setPermission($sid, $oid, $mask);

                    $chainMetadataProvider->stopProviderEmulation();
                }
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        /** @var RoleRepository $roleRepository */
        $roleRepository = $manager->getRepository(Role::class);
        /** @var Role $role */
        $role = $roleRepository->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        $user = $roleRepository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
