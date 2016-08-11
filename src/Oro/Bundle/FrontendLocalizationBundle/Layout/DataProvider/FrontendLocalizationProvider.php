<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

class FrontendLocalizationProvider
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
    public function getEnabledLocalizations()
    {
        return $this->userLocalizationManager->getEnabledLocalizations();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        return $this->userLocalizationManager->getCurrentLocalization();
    }
}
