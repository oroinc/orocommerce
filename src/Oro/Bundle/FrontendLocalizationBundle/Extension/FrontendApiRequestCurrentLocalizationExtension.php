<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Extension;

use Oro\Bundle\ApiBundle\Exception\InvalidHeaderValueException;
use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tries to get a current localization from "X-Localization-ID" header of a storefront API request.
 */
class FrontendApiRequestCurrentLocalizationExtension implements CurrentLocalizationExtensionInterface
{
    private const LOCALIZATION_ID_HEADER = 'X-Localization-ID';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LocalizationManager $localizationManager,
        private readonly ConfigManager $configManager,
        private readonly FrontendHelper $frontendHelper,
        private readonly ApiRequestHelper $apiRequestHelper
    ) {
    }

    #[\Override]
    public function getCurrentLocalization(): ?Localization
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        if (!$this->frontendHelper->isFrontendUrl($request->getPathInfo())) {
            return null;
        }

        if (!$this->apiRequestHelper->isApiRequest($request->getPathInfo())) {
            return null;
        }

        $localizationId = $request->headers->get(self::LOCALIZATION_ID_HEADER);
        if (!$localizationId) {
            return null;
        }

        if (false === filter_var($localizationId, FILTER_VALIDATE_INT)) {
            throw new InvalidHeaderValueException(\sprintf(
                'Expected integer value. Given "%s". Header: %s.',
                $localizationId,
                self::LOCALIZATION_ID_HEADER
            ));
        }

        $localizationId = (int)$localizationId;
        $enabledLocalizationIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)
        );
        if (!\in_array($localizationId, $enabledLocalizationIds, true)) {
            throw new InvalidHeaderValueException(\sprintf(
                'The value "%s" is unknown localization ID. Available values: %s. Header: %s.',
                $localizationId,
                implode(', ', $enabledLocalizationIds),
                self::LOCALIZATION_ID_HEADER
            ));
        }

        return $this->localizationManager->getLocalization($localizationId);
    }
}
