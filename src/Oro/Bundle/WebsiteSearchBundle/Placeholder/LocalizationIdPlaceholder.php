<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;

/**
 * The placeholder for the current localization ID.
 */
class LocalizationIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'LOCALIZATION_ID';

    /**
     * @var CurrentLocalizationProvider
     */
    private $localizationProvider;

    public function __construct(CurrentLocalizationProvider $localizationProvider)
    {
        $this->localizationProvider = $localizationProvider;
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
            throw new \RuntimeException('Can\'t get current localization');
        }

        return (string) $localization->getId();
    }
}
