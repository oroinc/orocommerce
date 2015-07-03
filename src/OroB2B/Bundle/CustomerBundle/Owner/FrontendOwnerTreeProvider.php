<?php

namespace OroB2B\Bundle\CustomerBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

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
            $this->cache = $this->getContainer()->get('orob2b_customer.owner.frontend_ownership_tree_provider.cache');
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
                ->get('orob2b_customer.owner.frontend_ownership_metadata_provider');
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
    protected function fillTree(OwnerTree $tree)
    {
        $accountUserClass = $this->getOwnershipMetadataProvider()->getBasicLevelClass();
        $customerClass = $this->getOwnershipMetadataProvider()->getLocalLevelClass();

        /** @var AccountUser[] $accountUsers */
        $accountUsers = $this->getManagerForClass($accountUserClass)->getRepository($accountUserClass)->findAll();

        /** @var Customer[] $customers */
        $customers = $this->getManagerForClass($customerClass)->getRepository($customerClass)->findAll();

        // map customers
        foreach ($customers as $customer) {
            if ($customer->getOrganization()) {
                $tree->addLocalEntity($customer->getId(), $customer->getOrganization()->getId());
                if ($customer->getParent()) {
                    $tree->addDeepEntity($customer->getId(), $customer->getParent()->getId());
                }
            }
        }

        // map users
        foreach ($accountUsers as $user) {
            $customer = $user->getCustomer();
            $tree->addBasicEntity($user->getId(), $customer ? $customer->getId() : null);

            foreach ($user->getOrganizations() as $organization) {
                $organizationId = $organization->getId();
                $tree->addGlobalEntity($user->getId(), $organizationId);
                if ($organizationId === $customer->getOrganization()->getId()) {
                    $tree->addLocalEntityToBasic($user->getId(), $customer->getId(), $organizationId);
                }
            }
        }
    }
}
