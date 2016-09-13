<?php

namespace Oro\Bundle\AccountBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Form\Type\FrontendAccountUserRegistrationType;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class FrontendAccountUserRegistrationFormProvider extends AbstractFormProvider
{
    const ACCOUNT_USER_REGISTER_ROUTE_NAME = 'oro_account_frontend_account_user_register';
    
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigManager */
    private $configManager;

    /** @var WebsiteManager */
    private $websiteManager;

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
     * @return FormAccessor
     */
    public function getRegisterForm()
    {
        return $this->getFormAccessor(
            FrontendAccountUserRegistrationType::NAME,
            self::ACCOUNT_USER_REGISTER_ROUTE_NAME,
            $this->createAccountUser()
        );
    }

    /**
     * @return AccountUser
     *
     * TODO: remove logic with creating new account user from data provider
     */
    private function createAccountUser()
    {
        $accountUser = new AccountUser();

        $defaultOwnerId = $this->configManager->get('oro_b2b_account.default_account_owner');
        if (!$defaultOwnerId) {
            throw new \RuntimeException('Application Owner is empty');
        }

        $website = $this->websiteManager->getCurrentWebsite();
        if (!$website) {
            throw new \RuntimeException('Website is empty');
        }

        /** @var Organization $organization */
        $organization = $website->getOrganization();
        if (!$organization) {
            throw new \RuntimeException('Website organization is empty');
        }

        $defaultRole = $this->managerRegistry
            ->getManagerForClass('OroAccountBundle:AccountUserRole')
            ->getRepository('OroAccountBundle:AccountUserRole')
            ->getDefaultAccountUserRoleByWebsite($website);

        if (!$defaultRole) {
            throw new \RuntimeException(sprintf('Role "%s" was not found', AccountUser::ROLE_DEFAULT));
        }

        /** @var User $owner */
        $owner = $this->userManager->getRepository()->find($defaultOwnerId);

        $accountUser
            ->setOwner($owner)
            ->addOrganization($organization)
            ->setOrganization($organization)
            ->addRole($defaultRole);

        return $accountUser;
    }
}
