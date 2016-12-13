<?php

namespace Oro\Bundle\RedirectBundle\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CanonicalDataProvider
{
    /**
     * @var RoutingInformationProvider
     */
    protected $routingInformationProvider;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param UrlGeneratorInterface $router
     * @param ConfigManager $configManager
     * @param RequestStack $requestStack,
     * @param RoutingInformationProvider $routingInformationProvider
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        UrlGeneratorInterface $router,
        ConfigManager $configManager,
        RequestStack $requestStack,
        RoutingInformationProvider $routingInformationProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->router = $router;
        $this->configManager = $configManager;
        $this->requestStack = $requestStack;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param SluggableInterface $data
     * @return string
     */
    public function getUrl(SluggableInterface $data)
    {
        $url = null;
        $canonicalUrlType = $this->configManager->get('oro_redirect.canonical_url_type');
        if ($canonicalUrlType === Configuration::DIRECT_URL && $slug = $this->getDirectUrlSlug($data)) {
            $url = $this->requestStack->getMasterRequest()->getUriForPath($slug->getUrl());
        }

        if (!$url) {
            $routeData = $this->routingInformationProvider->getRouteData($data);

            $url = $this->router->generate(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return $url;
    }

    /**
     * @param SluggableInterface $data
     * @return Slug|null
     */
    protected function getDirectUrlSlug(SluggableInterface $data)
    {
        $currentLocalization = $this->localizationHelper->getCurrentLocalization();

        $slug = $this->getLocalizationSlug($data->getSlugs(), $currentLocalization->getId());

        if (!$slug) {
            $slug = $this->getFallbackLocalizationSlug($data->getSlugs(), $currentLocalization);
        }

        return $slug;
    }

    /**
     * @param Collection $slugs
     * @param Localization $localization
     * @return Slug|null
     */
    protected function getFallbackLocalizationSlug(Collection $slugs, Localization $localization)
    {
        $hierarchy = $localization->getHierarchy();

        foreach ($hierarchy as $localizationId) {
            $slug = $this->getLocalizationSlug($slugs, $localizationId);
            if ($slug) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @param Collection $slugs
     * @param int|null $localizationId
     * @return Slug|null
     */
    protected function getLocalizationSlug(Collection $slugs, $localizationId = null)
    {
        foreach ($slugs as $slug) {
            $slugLocalizationId = null;
            if ($slug->getLocalization()) {
                $slugLocalizationId = $slug->getLocalization()->getId();
            }
            if ($slugLocalizationId === $localizationId) {
                return $slug;
            }
        }

        return null;
    }
}
