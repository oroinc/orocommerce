<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadUserRolesData;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UpdateCatalogManagerPermissions extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserRolesData::class];
    }

    /**
     * Load ACL for security roles
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->objectManager = $manager;

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        if ($aclManager->isAclEnabled()) {
            $this->updateCatalogManagerRole($aclManager);
            $aclManager->flush();
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function updateCatalogManagerRole(AclManager $manager)
    {
        $role = $this->objectManager
            ->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => 'ROLE_CATALOG_MANAGER']);

        if ($role) {
            $this->updatePermissionsForAttributeFamily($manager, $role);
            $this->updatePermissionsForAttribute($manager, $role);
        }
    }

    /**
     * @param AclManager $manager
     * @param Role $role
     */
    protected function updatePermissionsForAttributeFamily(AclManager $manager, Role $role)
    {
        $sid = $manager->getSid($role);
        $oid = $manager->getOid('entity:Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily');

        $extension = $manager->getExtensionSelector()->select($oid);
        $maskBuilders = $extension->getAllMaskBuilders();

        $permissions = ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM', 'ASSIGN_SYSTEM'];
        foreach ($maskBuilders as $maskBuilder) {
            foreach ($permissions as $permission) {
                if ($maskBuilder->hasMask('MASK_' . $permission)) {
                    $maskBuilder->add($permission);
                }
            }

            $manager->setPermission($sid, $oid, $maskBuilder->get());
        }
    }

    /**
     * @param AclManager $manager
     * @param Role $role
     */
    protected function updatePermissionsForAttribute(AclManager $manager, Role $role)
    {
        $acls = [
            'action:oro_attribute_create',
            'action:oro_attribute_update',
            'action:oro_attribute_view',
            'action:oro_attribute_remove',
        ];

        $sid = $manager->getSid($role);
        foreach ($acls as $acl) {
            $oid = $manager->getOid($acl);

            $extension = $manager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            $permission = 'EXECUTE';
            foreach ($maskBuilders as $maskBuilder) {
                if ($maskBuilder->hasMask('MASK_' . $permission)) {
                    $maskBuilder->add($permission);
                }

                $manager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }
    }
}
