<?php

namespace Oro\Bundle\FrontendNavigationBundle\Menu;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\FrontendNavigationBundle\Entity\Repository\MenuUpdateRepository;

class MenuUpdateProvider
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param SecurityFacade $securityFacade
     * @param WebsiteManager $websiteManager
     * @param ManagerRegistry $registry
     */
    public function __construct(
        SecurityFacade $securityFacade,
        WebsiteManager $websiteManager,
        ManagerRegistry $registry
    ) {
        $this->securityFacade = $securityFacade;
        $this->websiteManager = $websiteManager;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdates($menu)
    {
        $organization = $this->securityFacade->getOrganization() ? $this->securityFacade->getOrganization() : null;
        $accountUser  = $this->securityFacade->getLoggedUser();
        $account      = $accountUser !== null ? $accountUser->getAccount() : null;
        $website      = $this->websiteManager->getCurrentWebsite();

        /** @var MenuUpdateRepository $menuUpdateRepository */
        $menuUpdateRepository = $this->registry
            ->getManagerForClass('OroFrontendNavigationBundle:MenuUpdate')
            ->getRepository('OroFrontendNavigationBundle:MenuUpdate');

        return $menuUpdateRepository->getUpdates($menu, $organization, $account, $accountUser, $website);
    }
}
