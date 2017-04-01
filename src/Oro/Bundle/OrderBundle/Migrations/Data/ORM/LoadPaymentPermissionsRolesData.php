<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class LoadPaymentPermissionsRolesData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @internal
     */
    const VIEW_PAYMENT_HISTORY_PERMISSION = 'VIEW_PAYMENT_HISTORY_SYSTEM';

    /**
     * @internal
     */
    const CHARGE_AUTHORIZED_PAYMENTS_PERMISSION = 'CHARGE_AUTHORIZED_PAYMENTS_SYSTEM';

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
            $sid = $aclManager->getSid($role);

            $this->setPermissions(
                $aclManager,
                $sid,
                $this->getOrderEntityOid(),
                [
                    self::VIEW_PAYMENT_HISTORY_PERMISSION,
                    self::CHARGE_AUTHORIZED_PAYMENTS_PERMISSION,
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
            ->getEntityRepository('OroUserBundle:Role')->findBy([
                'role' => [
                    User::ROLE_ADMINISTRATOR,
                ],
            ]);
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
        return 'entity:Oro\Bundle\OrderBundle\Entity\Order';
    }
}
