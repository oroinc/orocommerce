<?php

namespace Oro\Bundle\ConsentBundle\Builder;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\CmsPageHelper;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Use it to build CmsPageData DTO from Consent object and ConsentAcceptance if present
 */
class CmsPageDataBuilder
{
    /** @var CmsPageHelper */
    protected $cmsPageHelper;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var RoutingInformationProviderInterface */
    protected $routingInformationProvider;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param CmsPageHelper $cmsPageHelper
     * @param LocalizationHelper $localizationHelper
     * @param RoutingInformationProviderInterface $routingInformationProvider
     * @param RouterInterface $router
     */
    public function __construct(
        CmsPageHelper $cmsPageHelper,
        LocalizationHelper $localizationHelper,
        RoutingInformationProviderInterface $routingInformationProvider,
        RouterInterface $router
    ) {
        $this->cmsPageHelper = $cmsPageHelper;
        $this->localizationHelper = $localizationHelper;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->router = $router;
    }

    /**
     * @param Consent $consent
     * @param ConsentAcceptance|null $consentAcceptance
     *
     * @return null|CmsPageData
     */
    public function build(Consent $consent, ConsentAcceptance $consentAcceptance = null)
    {
        $useCmsPageUrl = false;

        if ($consentAcceptance instanceof ConsentAcceptance) {
            $useCmsPageUrl = true;
        }

        $cmsPage = $this->cmsPageHelper->getCmsPage($consent, $consentAcceptance);

        if ($cmsPage instanceof Page) {
            return $this->getBuiltCmsPageData($consent, $cmsPage, $useCmsPageUrl);
        }

        return null;
    }

    /**
     * @param Consent $consent
     * @param Page $cmsPage
     * @param bool $useCmsPageUrl
     *
     * @return CmsPageData
     */
    protected function getBuiltCmsPageData(Consent $consent, Page $cmsPage, $useCmsPageUrl)
    {
        if ($useCmsPageUrl) {
            $routeData = $this->routingInformationProvider->getRouteData($cmsPage);
            $url = $this->router->generate(
                $routeData->getRoute(),
                $routeData->getRouteParameters()
            );
        } else {
            $localizedUrls = $consent->getContentNode()->getLocalizedUrls();
            $url = $this->router->getContext()->getBaseUrl()
                . $this->localizationHelper->getLocalizedValue($localizedUrls);
        }

        $cmsPageData = new CmsPageData();
        $cmsPageData->setId($cmsPage->getId());
        $cmsPageData->setUrl($url);

        return $cmsPageData;
    }
}
