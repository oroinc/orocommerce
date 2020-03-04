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

    /**
     * @return Localization|null
     */
    public function getDefaultLocalization(): ?Localization;

    /**
     * @param Website|null $website
     * @return Localization|null
     */
    public function getCurrentLocalization(Website $website = null): ?Localization;

    /**
     * @param CustomerUser $customerUser
     * @param Website|null $website
     * @return null|Localization
     */
    public function getCurrentLocalizationByCustomerUser(
        CustomerUser $customerUser,
        Website $website = null
    ): ?Localization;

    /**
     * @param Localization $localization
     * @param Website|null $website
     * @param bool $forceSessionStart Sets localization to the session even if it was not started.
     */
    public function setCurrentLocalization(
        Localization $localization,
        Website $website = null,
        $forceSessionStart = false
    ): void;
}
