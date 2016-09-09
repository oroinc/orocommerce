<?php

namespace Oro\Bundle\FrontendNavigationBundle\Provider;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Provider\AbstractMenuUpdateProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\FrontendNavigationBundle\Entity\Repository\MenuUpdateRepository;

class MenuUpdateProvider extends AbstractMenuUpdateProvider
{
    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteManager $websiteManager
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper,
        WebsiteManager $websiteManager,
        LocalizationHelper $localizationHelper
    ) {
        parent::__construct($securityFacade, $doctrineHelper);

        $this->websiteManager = $websiteManager;
        $this->localizationHelper = $localizationHelper;
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
        foreach ($updates as $update) {
            $update->setTitle($this->localizationHelper->getLocalizedValue($update->getTitles()));
        }

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
