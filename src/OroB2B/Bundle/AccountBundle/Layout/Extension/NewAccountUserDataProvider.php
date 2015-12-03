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
    /** @var AccountUser */
    protected $data;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param WebsiteManager $websiteManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(WebsiteManager $websiteManager, ManagerRegistry $doctrine)
    {
        $this->websiteManager = $websiteManager;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'newAccountUser';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = $this->getAccountUser();
        }

        return $this->data;
    }

    /**
     * @return AccountUser
     */
    protected function getAccountUser()
    {
        $accountUser = new AccountUser();

        /** @var WebsiteManager $websiteManager */
        $websiteManager = $this->websiteManager;
        $website = $websiteManager->getCurrentWebsite();
        /** @var Organization|OrganizationInterface $websiteOrganization */
        $websiteOrganization = $website->getOrganization();
        if (!$websiteOrganization) {
            throw new \RuntimeException('Website organization is empty');
        }
        $defaultRole = $this->doctrine
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
