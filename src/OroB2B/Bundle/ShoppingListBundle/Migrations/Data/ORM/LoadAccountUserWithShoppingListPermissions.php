<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class LoadAccountUserWithShoppingListPermissions extends AbstractFixture
    implements DependentFixtureInterface, ContainerAwareInterface
{
    const SHOPPING_LIST_CLASS = 'OroB2BShoppingListBundle:ShoppingList';
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
        return ['OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAccountUserRoles'];
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
            foreach ($aclManager->getAllExtensions() as $extension) {
                if ($extension instanceof EntityAclExtension) {
                    $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

                    $oid = $aclManager->getOid('entity:' . self::SHOPPING_LIST_CLASS);
                    $builder = $aclManager->getMaskBuilder($oid);
                    $mask = $builder->reset()->get();
                    foreach ($allowedAcls as $acl) {
                        $mask = $builder->add($acl)->get();
                    }
                    $aclManager->setPermission($sid, $oid, $mask);

                    $chainMetadataProvider->stopProviderEmulation();
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
        return $manager->getRepository('OroB2BCustomerBundle:AccountUserRole')
            ->findOneBy(['role' => self::ROLE_FRONTEND_BUYER]);
    }
}
