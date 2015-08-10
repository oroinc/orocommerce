<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class LoadBuyerPermissions extends AbstractFixture implements
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
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $allowedAclCollection = ['VIEW_BASIC', 'CREATE_BASIC', 'EDIT_BASIC', 'DELETE_BASIC'];
        $role = $this->getBuyerRole($manager);

        $sid = $aclManager->getSid($role);
        $classNames = [
            $this->container->getParameter('orob2b_shopping_list.entity.shopping_list.class'),
            $this->container->getParameter('orob2b_shopping_list.entity.line_item.class')
        ];

        foreach ($classNames as $className) {
            $this->setAllowedAclForClass(
                $allowedAclCollection,
                $chainMetadataProvider,
                $aclManager,
                $className,
                $sid
            );
        }
    }

    /**
     * @param array $allowedAclCollection
     * @param ChainMetadataProvider $chainMetadataProvider
     * @param AclManager $aclManager
     * @param string $className
     * @param SID $sid
     */
    protected function setAllowedAclForClass(
        array $allowedAclCollection,
        ChainMetadataProvider $chainMetadataProvider,
        AclManager $aclManager,
        $className,
        $sid
    ) {
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
        $oid = $aclManager->getOid('entity:' . $className);
        $builder = $aclManager->getMaskBuilder($oid);
        $mask = $builder->reset()->get();

        foreach ($allowedAclCollection as $acl) {
            $mask = $builder->add($acl)->get();
        }
        $aclManager->setPermission($sid, $oid, $mask);
        $chainMetadataProvider->stopProviderEmulation();
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
