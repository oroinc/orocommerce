<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

class SetPriceListRecalculatePermission extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    const RECALCULATE_PERMISSION = 'RECALCULATE';

    /** @var ObjectManager */
    protected $objectManager;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }

        $this->objectManager = $manager;

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        if ($aclManager->isAclEnabled()) {
            $this->setRecalculatePriceListPermission($aclManager);
        }
    }

    /**
     * @param AclManager $manager
     */
    protected function setRecalculatePriceListPermission(AclManager $manager)
    {
        $privilegeRepository = $this->container->get('oro_security.acl.privilege_repository');
        $oid = $manager->getOid('entity:Oro\Bundle\PricingBundle\Entity\PriceList');
        $extension = $manager->getExtensionSelector()->select($oid);
        $maskBuilder = $extension->getMaskBuilder(self::RECALCULATE_PERMISSION);

        /** @var Role $role */
        foreach ($this->getRoleRepository()->findAll() as $role) {
            $sid = $manager->getSid($role);
            $allRolePrivileges = $privilegeRepository->getPrivileges($sid);
            /** @var AclPrivilege $aclPrivilege */
            foreach ($allRolePrivileges as $aclPrivilege) {
                if ($aclPrivilege->getIdentity()->getId() !== 'entity:Oro\Bundle\PricingBundle\Entity\PriceList') {
                    continue;
                }
                $editAccessLevel = $aclPrivilege->getPermissions()->get('EDIT')->getAccessLevel();
                $level = AccessLevel::getAccessLevelNames()[$editAccessLevel];
                $maskName = self::RECALCULATE_PERMISSION . '_' . $level;

                if ($maskBuilder->hasMask('MASK_' . $maskName)) {
                    $maskBuilder->add($maskName);
                    $manager->setPermission($sid, $oid, $maskBuilder->get());
                }
            }
        }
        $manager->flush();
    }

    /**
     * @param string $roleName
     * @return EntityRepository
     */
    protected function getRoleRepository()
    {
        return $this->objectManager->getRepository(Role::class);
    }
}
