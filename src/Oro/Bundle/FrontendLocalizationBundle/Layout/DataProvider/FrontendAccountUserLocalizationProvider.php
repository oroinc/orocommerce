<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

class FrontendAccountUserLocalizationProvider
{
    /**
     * @var UserLocalizationManager
     */
    protected $userLocalizationManager;

    /**
     * @param UserLocalizationManager $userLocalizationManager
     */
    public function __construct(UserLocalizationManager $userLocalizationManager)
    {
        $this->userLocalizationManager = $userLocalizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        return $this->userLocalizationManager->getCurrentLocalization();
    }
}
