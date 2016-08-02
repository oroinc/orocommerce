<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class FrontendAccountUserLocalizationProvider implements DataProviderInterface
{
    const NAME = 'frontend_account_user_localization';

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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->userLocalizationManager->getCurrentLocalization();
    }
}
