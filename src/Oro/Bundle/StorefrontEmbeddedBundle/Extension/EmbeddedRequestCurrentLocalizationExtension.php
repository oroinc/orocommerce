<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Serves the storefront localization from the X-Localization-ID header on an embedded fragment request.
 */
class EmbeddedRequestCurrentLocalizationExtension implements CurrentLocalizationExtensionInterface
{
    private const string LOCALIZATION_ID_HEADER = 'X-Localization-ID';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly LocalizationManager $localizationManager,
        private readonly ConfigManager $configManager,
    ) {
    }

    #[\Override]
    public function getCurrentLocalization(): ?Localization
    {
        if (!$this->embeddedRequestChecker->rendersFragment()) {
            return null;
        }

        $localizationId = $this->requestStack->getMainRequest()?->headers->get(self::LOCALIZATION_ID_HEADER);
        if ($localizationId === null || filter_var($localizationId, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        $enabledLocalizationIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)
        );
        if (!\in_array((int) $localizationId, array_map('intval', (array) $enabledLocalizationIds), true)) {
            return null;
        }

        return $this->localizationManager->getLocalization((int) $localizationId);
    }
}
