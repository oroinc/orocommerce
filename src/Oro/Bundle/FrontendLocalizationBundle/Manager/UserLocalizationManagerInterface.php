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

    public function getCurrentLocalization(Website $website = null): ?Localization;

    public function getCurrentLocalizationByCustomerUser(
        CustomerUser $customerUser,
        Website $website = null
    ): ?Localization;

    public function setCurrentLocalization(Localization $localization, Website $website = null): void;
}
