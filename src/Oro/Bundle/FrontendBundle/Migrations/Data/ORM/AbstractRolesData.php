<?php

namespace Oro\Bundle\FrontendBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

abstract class AbstractRolesData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ROLES_FILE_NAME = '';

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
        $aclManager = $this->getAclManager();
        $roleData = $this->loadRolesData();

        foreach ($roleData as $roleName => $roleConfigData) {
            $role = $this->createEntity($roleName, $roleConfigData['label']);

            $manager->persist($role);

            if (!$aclManager->isAclEnabled()) {
                continue;
            }

            $sid = $aclManager->getSid($role);

            if (!empty($roleConfigData['max_permissions'])) {
                $this->setPermissionGroup($aclManager, $sid);
            }

            if (empty($roleConfigData['permissions']) || !is_array($roleConfigData['permissions'])) {
                continue;
            }

            $this->setPermissions($aclManager, $sid, $roleConfigData['permissions']);
        }

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param string $name
     * @param string $label
     * @return AbstractRole
     */
    abstract protected function createEntity($name, $label);

    /**
     * @return array
     */
    protected function loadRolesData()
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $bundles = array_keys($this->container->getParameter('kernel.bundles'));

        $rolesData = [];
        foreach ($bundles as $bundle) {
            $fileName = $this->getFileName($bundle);
            try {
                $file = $kernel->locateResource($fileName);
                $rolesData = array_merge_recursive($rolesData, Yaml::parse($file));
            } catch (\InvalidArgumentException $e) {
            }
        }

        return $rolesData;
    }

    /**
     * @param string $bundle
     * @return string
     */
    protected function getFileName($bundle)
    {
        return sprintf('@%s%s%s', $bundle, '/Migrations/Data/ORM/data/', static::ROLES_FILE_NAME);
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     */
    protected function setPermissionGroup(AclManager $aclManager, SecurityIdentityInterface $sid)
    {
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $fullAccessMask = $maskBuilder->hasMask('GROUP_SYSTEM')
                    ? $maskBuilder->getMask('GROUP_SYSTEM')
                    : $maskBuilder->getMask('GROUP_ALL');
                $aclManager->setPermission($sid, $rootOid, $fullAccessMask, true);
            }
        }
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     * @param array $permissions
     */
    protected function setPermissions(AclManager $aclManager, SecurityIdentityInterface $sid, array $permissions)
    {
        foreach ($permissions as $permission => $acls) {
            $oid = $aclManager->getOid(str_replace('|', ':', $permission));
            $extension = $aclManager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            foreach ($maskBuilders as $maskBuilder) {
                $maskBuilder->reset();

                if ($acls) {
                    foreach ($acls as $acl) {
                        if ($maskBuilder->hasMask('MASK_' . $acl)) {
                            $maskBuilder->add($acl);
                        }
                    }
                }

                $aclManager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }
    }

    /**
     * @return AclManager
     */
    protected function getAclManager()
    {
        return $this->container->get('oro_security.acl.manager');
    }
}
