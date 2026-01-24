<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Compares slug URLs before and after changes to identify modifications.
 *
 * This class analyzes collections of slug URLs to detect which URLs have changed between
 * two states. It organizes changes by locale label, making it easy to identify which
 * localized URLs have been modified and what the old and new values are.
 */
class SlugUrlDiffer
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LocalizationHelper $localizationHelper, TranslatorInterface $translator)
    {
        $this->localizationHelper = $localizationHelper;
        $this->translator = $translator;
    }

    /**
     * @param Collection|SlugUrl[] $slugUrlsBefore
     * @param Collection|SlugUrl[] $slugUrlsAfter
     * @return array
     */
    public function getSlugUrlsChanges(Collection $slugUrlsBefore, Collection $slugUrlsAfter)
    {
        $urlsBefore = $this->getUrlsByLocaleLabel($slugUrlsBefore);
        $urlsAfter = $this->getUrlsByLocaleLabel($slugUrlsAfter);

        $urlChanges = [];
        foreach ($urlsBefore as $localeLabel => $url) {
            if (!empty($urlsAfter[$localeLabel]) && $url !== $urlsAfter[$localeLabel]) {
                $urlChanges[$localeLabel]['before'] = $url;
                $urlChanges[$localeLabel]['after'] = $urlsAfter[$localeLabel];
            }
        }

        return $urlChanges;
    }

    /**
     * @param Collection|SlugUrl[] $slugUrls
     * @return array
     */
    private function getUrlsByLocaleLabel(Collection $slugUrls)
    {
        $defaultLocaleLabel = $this->translator->trans('oro.locale.fallback.type.default');

        $urlsByLocaleLabel = [];
        foreach ($slugUrls as $slugUrl) {
            $localization = $slugUrl->getLocalization();
            if (null === $localization) {
                $localeLabel = $defaultLocaleLabel;
            } else {
                $localeLabel = (string)$this->localizationHelper->getLocalizedValue($localization->getTitles());
            }

            $urlsByLocaleLabel[$localeLabel] = $slugUrl->getUrl();
        }

        return $urlsByLocaleLabel;
    }
}
