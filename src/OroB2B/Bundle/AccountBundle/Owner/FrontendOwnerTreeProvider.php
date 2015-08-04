<?php

namespace OroB2B\Bundle\AccountBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class FrontendOwnerTreeProvider extends AbstractOwnerTreeProvider
{
    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var FrontendOwnershipMetadataProvider
     */
    private $ownershipMetadataProvider;

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof AccountUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getContainer()->get('orob2b_account.owner.frontend_ownership_tree_provider.cache');
        }

        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadataProvider()
    {
        if (!$this->ownershipMetadataProvider) {
            $this->ownershipMetadataProvider = $this->getContainer()
                ->get('orob2b_account.owner.frontend_ownership_metadata_provider');
        }

        return $this->ownershipMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTreeData()
    {
        return new OwnerTree();
    }

    /**
     * {@inheritdoc}
     */
    protected function fillTree(OwnerTreeInterface $tree)
    {
        $accountUserClass = $this->getOwnershipMetadataProvider()->getBasicLevelClass();
        $accountClass = $this->getOwnershipMetadataProvider()->getLocalLevelClass();

        /** @var AccountUser[] $accountUsers */
        $accountUsers = $this->getManagerForClass($accountUserClass)->getRepository($accountUserClass)->findAll();

        /** @var Account[] $accounts */
        $accounts = $this->getManagerForClass($accountClass)->getRepository($accountClass)->findAll();

        // map accounts
        foreach ($accounts as $account) {
            if ($account->getOrganization()) {
                $tree->addLocalEntity($account->getId(), $account->getOrganization()->getId());
                if ($account->getParent()) {
                    $tree->addDeepEntity($account->getId(), $account->getParent()->getId());
                }
            }
        }

        // map users
        foreach ($accountUsers as $user) {
            $account = $user->getAccount();
            $tree->addBasicEntity($user->getId(), $account ? $account->getId() : null);

            foreach ($user->getOrganizations() as $organization) {
                $organizationId = $organization->getId();
                $tree->addGlobalEntity($user->getId(), $organizationId);
                if ($organizationId === $account->getOrganization()->getId()) {
                    $tree->addLocalEntityToBasic($user->getId(), $account->getId(), $organizationId);
                }
            }
        }
    }
}
