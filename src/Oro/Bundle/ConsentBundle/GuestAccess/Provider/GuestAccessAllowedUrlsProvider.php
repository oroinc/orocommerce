<?php

namespace Oro\Bundle\ConsentBundle\GuestAccess\Provider;

use Oro\Bundle\ConsentBundle\Builder\CmsPageDataBuilder;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\Routing\RequestContext;

/**
 * Provides a list of patterns for URLs for which an access is granted for non-authenticated visitors.
 */
class GuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /**
     * @var string[]
     */
    private $allowedUrls = [];

    /**
     * @var EnabledConsentProvider
     */
    private $consentProvider;

    /**
     * @var CmsPageDataBuilder
     */
    private $cmsPageDataBuilder;

    /**
     * @var RequestContext
     */
    private $requestContext;

    public function __construct(
        EnabledConsentProvider $consentProvider,
        CmsPageDataBuilder $cmsPageDataBuilder,
        RequestContext $requestContext
    ) {
        $this->consentProvider = $consentProvider;
        $this->cmsPageDataBuilder = $cmsPageDataBuilder;
        $this->requestContext = $requestContext;
    }

    /**
     * Adds a pattern to the list of allowed URL patterns.
     *
     * @param string $pattern
     */
    public function addAllowedUrlPattern($pattern)
    {
        $this->allowedUrls[] = $pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedUrlsPatterns(): array
    {
        $allowedUrlsByConsents = [];
        $consents = $this->consentProvider->getConsents();
        $baseUrl = $this->requestContext->getBaseUrl();
        foreach ($consents as $consent) {
            $cmsPageData = $this->cmsPageDataBuilder->build($consent);
            if (null === $cmsPageData) {
                continue;
            }
            $allowedUrlsByConsents[] = '^' . UrlUtil::getPathInfo($cmsPageData->getUrl(), $baseUrl) . '$';
        }

        return \array_merge($this->allowedUrls, $allowedUrlsByConsents);
    }
}
