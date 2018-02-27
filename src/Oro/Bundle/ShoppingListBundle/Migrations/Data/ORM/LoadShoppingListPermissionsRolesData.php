<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class LoadShoppingListPermissionsRolesData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @internal
     */
    const DUPLICATE_SHOPPING_LIST_PERMISSION = 'DUPLICATE_SHOPPING_LIST_BASIC';

    /**
     * @internal
     */
    const DUPLICATE_SHOPPING_LIST_PERMISSION_DEEP = 'DUPLICATE_SHOPPING_LIST_DEEP';

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

        foreach ($roles as $role) {
            if ($role->getRole() === 'ROLE_FRONTEND_ANONYMOUS') {
                continue;
            }
            $permission = self::DUPLICATE_SHOPPING_LIST_PERMISSION;
            if ($role->getRole() === 'ROLE_FRONTEND_ADMINISTRATOR') {
                $permission = self::DUPLICATE_SHOPPING_LIST_PERMISSION_DEEP;
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
            ->getEntityRepository(CustomerUserRole::class)->findAll();
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
