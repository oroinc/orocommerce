<?php

namespace OroB2B\Bundle\AccountBundle\Layout\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class NewAccountUserDataProvider implements DataProviderInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        return $this->getAccountUser();
    }

    /**
     * @return AccountUser
     */
    protected function getAccountUser()
    {
        $accountUser = new AccountUser();

        /** @var WebsiteManager $websiteManager */
        $websiteManager = $this->container->get('orob2b_website.manager');
        $website = $websiteManager->getCurrentWebsite();
        /** @var Organization|OrganizationInterface $websiteOrganization */
        $websiteOrganization = $website->getOrganization();
        if (!$websiteOrganization) {
            throw new \RuntimeException('Website organization is empty');
        }
        $defaultRole = $this->container->get('doctrine')
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
