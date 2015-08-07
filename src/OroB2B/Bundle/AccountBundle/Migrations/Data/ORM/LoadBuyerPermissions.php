<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class LoadBuyerPermissions extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ROLE_FRONTEND_BUYER = 'ROLE_FRONTEND_BUYER';
    const ACTION_ID = 'orob2b_product_view_products';
    const ACCOUNT_USER_ROLE_CLASS = 'orob2b_account.entity.account_user_role.class';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAccountUserRoles'];
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

        $this->setBuyerPermissions($manager, $aclManager);

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AclManager    $aclManager
     */
    protected function setBuyerPermissions(ObjectManager $manager, AclManager $aclManager)
    {
        if ($aclManager->isAclEnabled()) {
            $role = $this->getBuyerRole($manager);
            $sid = $aclManager->getSid($role);

            foreach ($aclManager->getAllExtensions() as $extension) {
                if ($extension instanceof ActionAclExtension) {
                    $oid = $aclManager->getOid('action:' . self::ACTION_ID);
                    $builder = $aclManager->getMaskBuilder($oid);
                    $mask = $builder->reset()->add('EXECUTE')->get();
                    $aclManager->setPermission($sid, $oid, $mask, true);
                }
            }
        }
    }

    /**
     * @param ObjectManager $manager
     *
     * @return AccountUserRole
     */
    protected function getBuyerRole(ObjectManager $manager)
    {
        return $manager
            ->getRepository($this->container->getParameter(self::ACCOUNT_USER_ROLE_CLASS))
            ->findOneBy(['role' => self::ROLE_FRONTEND_BUYER]);
    }
}
