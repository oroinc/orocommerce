<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoles extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ADMINISTRATOR = 'ADMINISTRATOR';
    const BUYER = 'BUYER';

    /**
     * @var array
     */
    protected $defaultRoles = [
        self::ADMINISTRATOR => 'Administrator',
        self::BUYER => 'Buyer',
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData'];
    }

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
        $aclManager = $this->container->get('oro_security.acl.manager');

        $administrator = $this->createAdministratorRole($aclManager);
        $buyer = $this->createBuyerRole($manager, $aclManager);

        $manager->persist($administrator);
        $manager->persist($buyer);

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param AclManager $aclManager
     * @return AccountUserRole
     */
    protected function createAdministratorRole(AclManager $aclManager)
    {
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $frontendMetadataProvider = $this->container->get('orob2b_customer.owner.frontend_ownership_metadata_provider');

        $allowedEntities = $this->getFrontendOwnedEntities();
        $allowedAcls = ['VIEW_LOCAL', 'CREATE_LOCAL', 'EDIT_LOCAL', 'DELETE_LOCAL', 'ASSIGN_LOCAL'];

        $role = $this->createEntity(self::ADMINISTRATOR, $this->defaultRoles[self::ADMINISTRATOR]);

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);
            foreach ($aclManager->getAllExtensions() as $extension) {
                if ($extension instanceof EntityAclExtension) {
                    $chainMetadataProvider->startProviderEmulation($frontendMetadataProvider);

                    foreach ($allowedEntities as $className) {
                        $oid = $aclManager->getOid('entity:' . $className);
                        $builder = $aclManager->getMaskBuilder($oid);
                        $mask = $builder->reset()->get();
                        foreach ($allowedAcls as $acl) {
                            $mask = $builder->add($acl)->get();
                        }
                        $aclManager->setPermission($sid, $oid, $mask);
                    }

                    $chainMetadataProvider->stopProviderEmulation();
                } else {
                    $this->setPermissionGroup($aclManager, $extension, $sid, 'GROUP_ALL');
                }
            }
        }

        return $role;
    }

    /**
     * @param ObjectManager $manager
     * @param AclManager $aclManager
     * @return AccountUserRole
     */
    protected function createBuyerRole(ObjectManager $manager, AclManager $aclManager)
    {
        $role = $this->createEntity(self::BUYER, $this->defaultRoles[self::BUYER]);

        $this->setWebsiteDefaultRoles($manager, $role);

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);

            foreach ($aclManager->getAllExtensions() as $extension) {
                $this->setPermissionGroup($aclManager, $extension, $sid, 'GROUP_NONE');
            }
        }

        return $role;
    }

    /**
     * @param AclManager $aclManager
     * @param AclExtensionInterface $extension
     * @param SecurityIdentityInterface $sid
     * @param string $group
     */
    protected function setPermissionGroup(
        AclManager $aclManager,
        AclExtensionInterface $extension,
        SecurityIdentityInterface $sid,
        $group
    ) {
        $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
        foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
            if ($maskBuilder->hasConst($group)) {
                $mask = $maskBuilder->getConst($group);
                $aclManager->setPermission($sid, $rootOid, $mask, true);
                break;
            }
        }
    }

    /**
     * @return array
     */
    protected function getFrontendOwnedEntities()
    {
        $securityConfigProvider = $this->container->get('oro_entity_config.provider.security');
        $ownershipConfigProvider = $this->container->get('oro_entity_config.provider.ownership');

        $classes = [];

        foreach ($securityConfigProvider->getConfigs() as $config) {
            if ($config->has('group_name') && $config->get('group_name') == AccountUser::SECURITY_GROUP) {
                $classes[] = $config->getId()->getClassName();
            }
        }

        foreach ($classes as $key => $class) {
            if ($ownershipConfigProvider->hasConfig($class)) {
                $config = $ownershipConfigProvider->getConfig($class);
                if (!$config->has('frontend_owner_type')) {
                    unset($classes[$key]);
                }
            } else {
                unset($classes[$key]);
            }
        }

        return $classes;
    }

    /**
     * @param string $name
     * @param string $label
     * @return AccountUserRole
     */
    protected function createEntity($name, $label)
    {
        $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $name);
        $role->setLabel($label);

        return $role;
    }

    /**
     * @param ObjectManager $manager
     * @param AccountUserRole $role
     */
    protected function setWebsiteDefaultRoles(ObjectManager $manager, AccountUserRole $role)
    {
        $websites = $manager->getRepository('OroB2BWebsiteBundle:Website')->findAll();

        foreach ($websites as $website) {
            $role->addWebsite($website);
        }
    }
}
