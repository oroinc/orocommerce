<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadUserRolesData;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * Migration that set an appropriate permissions to Coupon entities for existing role ROLE_USER
 * Performed during the `oro:migration:data:load` (and `oro:platform:update`) command, skipped during the `oro:install`
 * command.
 */
class LoadCouponPermissionsForExistingRoles extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserRolesData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
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
                    'VIEW_SYSTEM',
                    'CREATE_SYSTEM',
                    'EDIT_SYSTEM',
                    'DELETE_SYSTEM',
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
            ->getEntityRepository(Role::class)->findBy([
                'role' => [
                    User::ROLE_DEFAULT,
                ],
            ]);
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     * @param string $oidDescriptor
     * @param array $acls
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
                if ($maskBuilder->hasMask('MASK_' . $acl)) {
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
        return 'entity:Oro\Bundle\PromotionBundle\Entity\Coupon';
    }
}
