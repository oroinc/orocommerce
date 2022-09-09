<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;

/**
 * The placeholder for the current localization ID.
 */
class LocalizationIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'LOCALIZATION_ID';

    private CurrentLocalizationProvider $localizationProvider;

    private LocalizationManager $localizationManager;

    public function __construct(
        CurrentLocalizationProvider $localizationProvider,
        LocalizationManager $localizationManager
    ) {
        $this->localizationProvider = $localizationProvider;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        $localization = $this->localizationProvider->getCurrentLocalization();

        if (!$localization) {
            $localization = $this->localizationManager->getDefaultLocalization();
        }

        if (!$localization) {
            throw new \RuntimeException('Can\'t get current localization');
        }

        return (string) $localization->getId();
    }
}
