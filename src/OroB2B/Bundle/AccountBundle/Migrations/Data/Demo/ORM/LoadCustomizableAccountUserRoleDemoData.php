<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class LoadCustomizableAccountUserRoleDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
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
        $accounts = $manager->getRepository('OroB2BAccountBundle:Account')->findAll();

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');

        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        /** @var Account $account */
        foreach ($accounts as $account) {
            $role = $this->createEntity($account->getName() . '_ROLE', $account->getName() . ' Role', $account);
            $manager->persist($role);

            if (!$aclManager->isAclEnabled()) {
                continue;
            }

            $this->setPermissionGroup($aclManager, $aclManager->getSid($role));
            $accountUsers = $account->getUsers();

            /** @var AccountUser $accountUser */
            foreach ($accountUsers as $accountUser) {
                $accountUser->addRole($role);
            }
        }

        $chainMetadataProvider->stopProviderEmulation();
        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param string  $name
     * @param string  $label
     * @param Account $account
     * @return AccountUserRole
     */
    protected function createEntity($name, $label, Account $account)
    {
        $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $name);
        $role->setLabel($label);
        $role->setAccount($account);
        $role->setOrganization($account->getOrganization());
        return $role;
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     * @param array $permissions
     */
    protected function setPermissions(AclManager $aclManager, SecurityIdentityInterface $sid, array $permissions)
    {
        foreach ($permissions as $permission => $acls) {
            $oid = $aclManager->getOid(str_replace('|', ':', $permission));
            $builder = $aclManager->getMaskBuilder($oid);
            $builder->reset();
            if ($acls) {
                foreach ($acls as $acl) {
                    $builder->add($acl);
                }
            }
            $mask = $builder->get();
            $aclManager->setPermission($sid, $oid, $mask);
        }
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     */
    protected function setPermissionGroup(AclManager $aclManager, SecurityIdentityInterface $sid)
    {
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $fullAccessMask = $maskBuilder->hasConst('GROUP_SYSTEM')
                    ? $maskBuilder->getConst('GROUP_SYSTEM')
                    : $maskBuilder->getConst('GROUP_ALL');
                $aclManager->setPermission($sid, $rootOid, $fullAccessMask, true);
            }
        }
    }
}
