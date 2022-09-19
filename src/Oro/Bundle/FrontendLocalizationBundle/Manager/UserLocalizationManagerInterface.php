<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Manager;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Interface that all UserLocalization Managers should implement
 */
interface UserLocalizationManagerInterface
{
    /**
     * @return Localization[]
     */
    public function getEnabledLocalizations(): array;

    public function getDefaultLocalization(): ?Localization;

    /**
     * Double thinking before you using this method to get localization, that this method returns uesr's preferred
     * localization, could not reflect accurately current localization for a site.
     * Mostly you can call LocalizationProviderInterface::getCurrentLocalization instead.
     */
    public function getCurrentLocalization(Website $website = null): ?Localization;

    public function getCurrentLocalizationByCustomerUser(
        CustomerUser $customerUser,
        Website $website = null
    ): ?Localization;

    public function setCurrentLocalization(Localization $localization, Website $website = null): void;
}
