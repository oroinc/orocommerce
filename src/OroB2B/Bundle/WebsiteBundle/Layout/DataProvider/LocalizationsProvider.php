<?php

namespace OroB2B\Bundle\WebsiteBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\WebsiteBundle\Manager\UserLocalizationManager;

class LocalizationsProvider implements DataProviderInterface
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
    public function getIdentifier()
    {
        return 'orob2b_website_localizations';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return [
            'localizations' => $this->userLocalizationManager->getEnabledLocalizations(),
            'current_localization' => $this->userLocalizationManager->getCurrentLocalization(),
        ];
    }
}
