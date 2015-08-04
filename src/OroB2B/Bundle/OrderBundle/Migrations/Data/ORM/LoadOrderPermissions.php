<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class LoadOrderPermissions extends AbstractFixture implements
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
        return [
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAccountUserRoles',
        ];
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
        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        $this->setBuyerOrderPermissions($manager, $aclManager);

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AclManager    $aclManager
     */
    protected function setBuyerOrderPermissions(ObjectManager $manager, AclManager $aclManager)
    {
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $allowedAcls = [
            'VIEW_BASIC',
            'CREATE_BASIC',
        ];
        $role = $this->getBuyerRole($manager, 'ROLE_FRONTEND_BUYER');

        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);
            $className = $this->container->getParameter('orob2b_order.entity.order.class');
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
                }
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $role
     *
     * @return AccountUserRole
     */
    protected function getBuyerRole(ObjectManager $manager, $role)
    {
        return $manager
            ->getRepository($this->container->getParameter('orob2b_customer.entity.account_user_role.class'))
            ->findOneBy(['role' => $role])
        ;
    }
}
