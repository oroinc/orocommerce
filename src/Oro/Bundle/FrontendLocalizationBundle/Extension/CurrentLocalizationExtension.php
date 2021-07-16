<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Extension;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Extension for CurrentLocalizationProvider. Provide possibility to get current localization.
 */
class CurrentLocalizationExtension implements CurrentLocalizationExtensionInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserLocalizationManagerInterface
     */
    protected $localizationManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserLocalizationManagerInterface $localizationManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @return Localization|null
     */
    public function getCurrentLocalization()
    {
        if ($this->getLoggedUser() instanceof User) {
            return null;
        }

        return $this->localizationManager->getCurrentLocalization();
    }

    /**
     * @return null|User|CustomerUser
     */
    protected function getLoggedUser()
    {
        $token = $this->tokenStorage->getToken();

        return $token ? $token->getUser() : null;
    }
}
