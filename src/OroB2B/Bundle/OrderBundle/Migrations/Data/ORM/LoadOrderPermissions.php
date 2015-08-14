<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

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
            'OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAccountUserRoles',
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

            $oid = $aclManager->getOid('entity:' . $className);
            $aclManager->getExtensionSelector()->select($oid);

            $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
            $builder = $aclManager->getMaskBuilder($oid);
            $mask = $builder->reset()->get();
            foreach ($allowedAcls as $acl) {
                $mask = $builder->add($acl)->get();
            }
            $aclManager->setPermission($sid, $oid, $mask);

            $chainMetadataProvider->stopProviderEmulation();
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
            ->getRepository($this->container->getParameter('orob2b_account.entity.account_user_role.class'))
            ->findOneBy(['role' => $role])
        ;
    }
}
