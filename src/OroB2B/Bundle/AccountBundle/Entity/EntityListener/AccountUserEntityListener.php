<?php

namespace OroB2B\Bundle\AccountBundle\Entity\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WebsiteProBundle\Manager\WebsiteManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserEntityListener
{
    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param AccountUser $accountUser
     * @param LifecycleEventArgs $event
     */
    public function prePersist(AccountUser $accountUser, LifecycleEventArgs $event)
    {
        $accountUser->setWebsite($this->websiteManager->getCurrentWebsite());
    }
}
