<?php

namespace Oro\Bundle\FrontendNavigationBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendNavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Provider\AbstractMenuUpdateProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class MenuUpdateProvider extends AbstractMenuUpdateProvider
{
    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper,
        WebsiteManager $websiteManager
    ) {
        parent::__construct($securityFacade, $doctrineHelper);

        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdates($menu)
    {
        $organization = $this->getCurrentOrganization();
        $accountUser = $this->getCurrentAccountUser();
        $account = $this->getCurrentAccount($accountUser);
        $website = $this->websiteManager->getCurrentWebsite();

        /** @var MenuUpdateRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroFrontendNavigationBundle:MenuUpdate');

        $updates = $repository->getUpdates($menu, $organization, $account, $accountUser, $website);

        return $updates;
    }

    /**
     * @return null|AccountUser
     */
    private function getCurrentAccountUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof AccountUser) {
            return $user;
        }

        return null;
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return null|Account
     */
    private function getCurrentAccount(AccountUser $accountUser)
    {
        if ($accountUser !== null) {
            return $accountUser->getAccount();
        }

        return null;
    }
}
