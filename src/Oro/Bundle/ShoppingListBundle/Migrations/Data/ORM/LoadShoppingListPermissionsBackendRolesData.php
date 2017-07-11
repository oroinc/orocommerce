<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class LoadShoppingListPermissionsBackendRolesData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @internal
     */
    const DUPLICATE_SHOPPING_LIST_PERMISSION_NONE = 'DUPLICATE_SHOPPING_LIST_NONE';

    /**
     * @internal
     */
    const DUPLICATE_SHOPPING_LIST_PERMISSION_SYSTEM = 'DUPLICATE_SHOPPING_LIST_SYSTEM';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roles = $this->loadRoles();
        $rolesWithAccess = [
            'ROLE_ADMINISTRATOR',
            'ROLE_SALES_ASSISTANT'
        ];
        foreach ($roles as $role) {
            $permission = self::DUPLICATE_SHOPPING_LIST_PERMISSION_NONE;
            if (in_array($role->getRole(), $rolesWithAccess, true)) {
                $permission = self::DUPLICATE_SHOPPING_LIST_PERMISSION_SYSTEM;
            }
            $sid = $aclManager->getSid($role);

            $this->setPermissions(
                $aclManager,
                $sid,
                $this->getOrderEntityOid(),
                [
                    $permission,
                ]
            );
        }

        $aclManager->flush();
    }

    /**
     * @return Role[]
     */
    protected function loadRoles()
    {
        return $this->container->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Role::class)->findAll();
    }

    /**
     * @param AclManager                $aclManager
     * @param SecurityIdentityInterface $sid
     * @param string                    $oidDescriptor
     * @param array                     $acls
     */
    protected function setPermissions(
        AclManager $aclManager,
        SecurityIdentityInterface $sid,
        $oidDescriptor,
        array $acls
    ) {
        $oid = $aclManager->getOid($oidDescriptor);
        $extension = $aclManager->getExtensionSelector()->select($oid);
        $maskBuilders = $extension->getAllMaskBuilders();

        foreach ($maskBuilders as $maskBuilder) {
            $wasFound = false;

            foreach ($acls as $acl) {
                if ($maskBuilder->hasMask('MASK_'.$acl)) {
                    $maskBuilder->add($acl);
                    $wasFound = true;
                }
            }

            if ($wasFound) {
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

    /**
     * @return string
     */
    protected function getOrderEntityOid()
    {
        return 'entity:Oro\Bundle\ShoppingListBundle\Entity\ShoppingList';
    }
}
