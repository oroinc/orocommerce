<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class LoadAccountUserWithShoppingListPermissions extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    const ROLE_FRONTEND_BUYER = 'ROLE_FRONTEND_BUYER';

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

        $this->setBuyerShoppingListPermissions($manager, $aclManager);

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AclManager    $aclManager
     */
    protected function setBuyerShoppingListPermissions(ObjectManager $manager, AclManager $aclManager)
    {
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $allowedAcls = ['VIEW_BASIC', 'CREATE_BASIC', 'EDIT_BASIC', 'DELETE_BASIC'];
        $role = $this->getBuyerRole($manager);

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);
            $className = $this->container->getParameter('orob2b_shopping_list.entity.shopping_list.class');
            foreach ($aclManager->getAllExtensions() as $extension) {
                if ($extension instanceof EntityAclExtension) {
                    $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
                    $oid = $aclManager->getOid('entity:' . $className);
                    $builder = $aclManager->getMaskBuilder($oid);
                    $mask = $builder->reset()->get();
                    foreach ($allowedAcls as $acl) {
                        $mask = $builder->add($acl)->get();
                    }
                    $aclManager->setPermission($sid, $oid, $mask);

                    $chainMetadataProvider->stopProviderEmulation();
                } elseif ($extension instanceof ActionAclExtension) {
                    $oid = $aclManager->getOid('action:orob2b_shoppinglist_add_product');
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
            ->getRepository($this->container->getParameter('orob2b_account.entity.account_user_role.class'))
            ->findOneBy(['role' => self::ROLE_FRONTEND_BUYER]);
    }
}
