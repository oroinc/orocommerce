<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\FormProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class FrontendAccountUserRegistrationFormProvider extends FormProvider
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigManager */
    private $configManager;

    /** @var WebsiteManager */
    protected $websiteManager;

    /** @var UserManager */
    private $userManager;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ManagerRegistry $managerRegistry
     * @param ConfigManager $configManager
     * @param WebsiteManager $websiteManager
     * @param UserManager $userManager
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        WebsiteManager $websiteManager,
        UserManager $userManager
    ) {
        parent::__construct($formFactory);
        $this->managerRegistry = $managerRegistry;
        $this->configManager = $configManager;
        $this->websiteManager = $websiteManager;
        $this->userManager = $userManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getForm($data = null, array $options = [])
    {
        $accountUser = $data ? $data : $this->createAccountUser();
        parent::getForm($accountUser, $options);
    }

    /**
     * @return AccountUser
     */
    protected function createAccountUser()
    {
        $accountUser = new AccountUser();

        $defaultOwnerId = $this->configManager->get('oro_b2b_account.default_account_owner');

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

        if (!$defaultOwnerId) {
            throw new \RuntimeException('Application Owner is empty');
        }

        /** @var User $owner */
        $owner = $this->userManager->getRepository()->find($defaultOwnerId);

        $accountUser
            ->setOwner($owner)
            ->addOrganization($websiteOrganization)
            ->setOrganization($websiteOrganization)
            ->addRole($defaultRole);

        return $accountUser;
    }
}
