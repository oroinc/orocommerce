<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Serves the storefront localization from the X-Localization-ID header on an embedded fragment request.
 */
class EmbeddedRequestCurrentLocalizationExtension implements CurrentLocalizationExtensionInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string NOT_INTEGER_REASON = 'not_integer';
    private const string NOT_ENABLED_REASON = 'not_enabled';

    public function __construct(
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly LocalizationManager $localizationManager,
        private readonly ConfigManager $configManager,
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function getCurrentLocalization(): ?Localization
    {
        $localizationId = $this->embeddedRequestChecker
            ->getFragmentRequestHeader(EmbeddedRequestChecker::LOCALIZATION_ID_HEADER);
        if ($localizationId === null) {
            return null;
        }

        if (filter_var($localizationId, FILTER_VALIDATE_INT) === false) {
            $this->logRejectedHeader($localizationId, self::NOT_INTEGER_REASON);

            return null;
        }

        $enabledLocalizationIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)
        );
        if (!\in_array((int) $localizationId, array_map('intval', (array) $enabledLocalizationIds), true)) {
            $this->logRejectedHeader($localizationId, self::NOT_ENABLED_REASON);

            return null;
        }

        return $this->localizationManager->getLocalization((int) $localizationId);
    }

    private function logRejectedHeader(string $localizationId, string $reason): void
    {
        $this->logger->debug('Embedded localization header was rejected.', [
            'localization_id' => $localizationId,
            'reason' => $reason,
        ]);
    }
}
