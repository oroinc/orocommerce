<?php

namespace OroB2B\Bundle\AccountBundle\Layout\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class NewAccountUserDataProvider implements DataProviderInterface
{
    /** @var WebsiteManager */
    protected $websiteManager;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param WebsiteManager $websiteManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(WebsiteManager $websiteManager, ManagerRegistry $managerRegistry)
    {
        $this->websiteManager = $websiteManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'new_account_user';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->getAccountUser();
    }

    /**
     * @return AccountUser
     */
    protected function getAccountUser()
    {
        $accountUser = new AccountUser();

        $website = $this->websiteManager->getCurrentWebsite();
        /** @var Organization|OrganizationInterface $websiteOrganization */
        $websiteOrganization = $website->getOrganization();
        if (!$websiteOrganization) {
            throw new \RuntimeException('Website organization is empty');
        }
        $defaultRole = $this->managerRegistry
            ->getManagerForClass('OroB2BAccountBundle:AccountUserRole')
            ->getRepository('OroB2BAccountBundle:AccountUserRole')
            ->getDefaultAccountUserRoleByWebsite($website);
        if (!$defaultRole) {
            throw new \RuntimeException(sprintf('Role "%s" was not found', AccountUser::ROLE_DEFAULT));
        }
        $accountUser
            ->addOrganization($websiteOrganization)
            ->setOrganization($websiteOrganization)
            ->addRole($defaultRole);

        return $accountUser;
    }
}
