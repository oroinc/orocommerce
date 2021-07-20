<?php

namespace Oro\Bundle\SEOBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\AlternateUrl;
use Symfony\Component\Validator\Constraints\Locale;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Layout dataprovider for getting entity localized links.
 */
class LocalizedLinksDataProvider
{
    /**
     * @var CanonicalUrlGenerator
     */
    private $urlGenerator;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var UserLocalizationManagerInterface
     */
    private $userLocalizationManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        CanonicalUrlGenerator $urlGenerator,
        ConfigManager $configManager,
        UserLocalizationManagerInterface $userLocalizationManager,
        ValidatorInterface $validator
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->configManager = $configManager;
        $this->userLocalizationManager = $userLocalizationManager;
        $this->validator = $validator;
    }

    /**
     * @param SlugAwareInterface|SluggableInterface $data
     * @return array|AlternateUrl[]
     */
    public function getAlternates(SlugAwareInterface $data)
    {
        $localizationMap = $this->getApplicableLocalizationsMap();
        $result = [];
        if (!$localizationMap) {
            return $result;
        }

        if ($this->isDirectUrlSupported()) {
            $result = $this->getAlternateUrlsBasedOnSlugs($data, $localizationMap);
        }

        if (!$result && $data instanceof SluggableInterface) {
            $result = $this->getAlternateUrlsBasedOnSystemUrl($data);
        }

        return $result;
    }

    /**
     * @param SluggableInterface $data
     * @return array|AlternateUrl[]
     */
    private function getAlternateUrlsBasedOnSystemUrl(SluggableInterface $data)
    {
        return [
            new AlternateUrl($this->urlGenerator->getSystemUrl($data))
        ];
    }

    /**
     * @param SlugAwareInterface $data
     * @param array $localizations
     * @return array|AlternateUrl[]
     */
    private function getAlternateUrlsBasedOnSlugs(SlugAwareInterface $data, array $localizations)
    {
        $alternateUrls = [];
        foreach ($data->getSlugs() as $slug) {
            $localization = $slug->getLocalization();
            if ($localization && empty($localizations[$localization->getId()])) {
                continue;
            }

            $alternateUrls[] = new AlternateUrl(
                $this->urlGenerator->getAbsoluteUrl($slug->getUrl()),
                $localization
            );
        }

        return $alternateUrls;
    }

    /**
     * @return array
     */
    private function getApplicableLocalizationsMap()
    {
        $enabledLocalizations = $this->userLocalizationManager->getEnabledLocalizations();

        if (count($enabledLocalizations) <= 1) {
            return [];
        }

        $localizations = [];
        foreach ($enabledLocalizations as $localization) {
            $locale = new Locale(['canonicalize' => true]);
            if (!$this->validator->validate($localization->getLanguageCode(), $locale)->count()) {
                $localizations[$localization->getId()] = true;
            }
        }

        return $localizations;
    }

    /**
     * @return bool
     */
    private function isDirectUrlSupported()
    {
        $canonicalUrlType = $this->configManager->get('oro_redirect.canonical_url_type');
        $enableDirectUrl = $this->configManager->get('oro_redirect.enable_direct_url');

        return $canonicalUrlType === Configuration::DIRECT_URL && $enableDirectUrl;
    }
}
