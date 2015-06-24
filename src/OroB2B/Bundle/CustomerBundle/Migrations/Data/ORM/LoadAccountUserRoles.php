<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoles extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ADMINISTRATOR = 'ADMINISTRATOR';
    const BUYER = 'BUYER';

    /**
     * @var array
     *
     * TODO: Set correct default roles permissions in scope of https://magecore.atlassian.net/browse/BB-709
     */
    protected $defaultRoles = [
        self::ADMINISTRATOR => ['label' => 'Administrator', 'permission_masks' => ['GROUP_LOCAL', 'GROUP_ALL']],
        self::BUYER => ['label' => 'Buyer', 'permission_masks' => ['GROUP_NONE']],
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

        foreach ($this->defaultRoles as $name => $data) {
            $role = $this->createEntity($name, $data['label']);

            if ($name === self::BUYER) {
                $this->setWebsiteDefaultRoles($manager, $role);
            }

            $this->setEntityPermission($aclManager, $role, $data['permission_masks']);

            $manager->persist($role);
        }

        $manager->flush();
        $aclManager->flush();
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
     * @param AclManager $manager
     * @param AccountUserRole $role
     * @param array $permissionMasks
     */
    protected function setEntityPermission(AclManager $manager, AccountUserRole $role, array $permissionMasks)
    {
        if ($manager->isAclEnabled()) {
            $sid = $manager->getSid($role);

            foreach ($manager->getAllExtensions() as $extension) {
                $rootOid = $manager->getRootOid($extension->getExtensionKey());
                foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                    foreach ($permissionMasks as $permissionConst) {
                        if ($maskBuilder->hasConst($permissionConst)) {
                            $mask = $maskBuilder->getConst($permissionConst);
                            $manager->setPermission($sid, $rootOid, $mask, true);
                            break;
                        }
                    }
                }
            }
        }
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
