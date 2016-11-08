<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * Decorates default URL matcher. Perform URL matching with decorated system matcher.
 * If default matcher unable to resolve URL detects route name and route parameter by slug entity.
 * Able to decorate both types of base matcher: RequestMatcherInterface, UrlMatcherInterface
 */
class SlugUrlMatcher implements RequestMatcherInterface, UrlMatcherInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var bool
     */
    protected $environment;

    /**
     * @var array
     */
    protected $skippedUrlPatterns = [];

    /**
     * @var RequestMatcherInterface|UrlMatcherInterface
     */
    protected $baseMatcher;

    /**
     * @param RequestMatcherInterface|UrlMatcherInterface $baseMatcher
     * @param RouterInterface $router
     * @param ManagerRegistry $registry
     * @param FrontendHelper $frontendHelper
     * @param ScopeManager $scopeManager
     * @param boolean $installed
     * @param string $environment
     */
    public function __construct(
        $baseMatcher,
        RouterInterface $router,
        ManagerRegistry $registry,
        FrontendHelper $frontendHelper,
        ScopeManager $scopeManager,
        $installed,
        $environment
    ) {
        $this->baseMatcher = $baseMatcher;
        $this->router = $router;
        $this->registry = $registry;
        $this->installed = $installed;
        $this->scopeManager = $scopeManager;
        $this->frontendHelper = $frontendHelper;
        $this->environment = $environment;
    }

    /**
     * Skipped url pattern should start with slash.
     *
     * @param string $skippedUrlPattern
     * @param string $env
     */
    public function addSkippedUrlPattern($skippedUrlPattern, $env = 'prod')
    {
        $this->skippedUrlPatterns[$env][] = $skippedUrlPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        $attributes = [];
        try {
            $attributes = $this->baseMatcher->matchRequest($request);
        } catch (ResourceNotFoundException $e) {
            $url = $request->getPathInfo();
            if ($this->matches($url)) {
                $attributes = $this->getAttributes($url);
            }
        }

        if (!$attributes) {
            throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $request->getPathInfo()));
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $attributes = [];
        try {
            $attributes = $this->baseMatcher->match($pathinfo);
        } catch (ResourceNotFoundException $e) {
            if ($this->matches($pathinfo)) {
                $attributes = $this->getAttributes($pathinfo);
            }
        }

        if (!$attributes) {
            throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->baseMatcher->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->baseMatcher->getContext();
    }

    /**
     * @param string $url
     * @return bool
     */
    protected function matches($url)
    {
        if (!$this->installed) {
            return false;
        }

        if (!$this->frontendHelper->isFrontendUrl($url)) {
            return false;
        }

        if ($this->isSkippedUrl($url)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     * @return bool
     */
    protected function isSkippedUrl($url)
    {
        if (array_key_exists($this->environment, $this->skippedUrlPatterns)) {
            foreach ($this->skippedUrlPatterns[$this->environment] as $pattern) {
                if (strpos($url, $pattern) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @return array
     */
    protected function getAttributes($url)
    {
        $attributes = [];

        $url = $this->getCleanUrl($url);
        $slug = $this->getSlug($url);
        if (!$slug) {
            return $attributes;
        }

        $routeName = $slug->getRouteName();
        $routeParameters = $slug->getRouteParameters();

        $resolvedUrl = $this->getResolvedUrl($routeName, $routeParameters);
        $routeData = $this->router->match($resolvedUrl);

        if (array_key_exists('_controller', $routeData)) {
            $attributes['_route'] = $routeName;
            $attributes['_controller'] = $routeData['_controller'];
            $attributes = array_merge($attributes, $routeParameters);
            $attributes['_route_params'] = $routeParameters;
            $attributes['_resolved_slug_url'] = $resolvedUrl;
        }

        return $attributes;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function getCleanUrl($url)
    {
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    /**
     * @param string $url
     * @return Slug|null
     */
    protected function getSlug($url)
    {
        /** @var SlugRepository $repository */
        $repository = $this->registry
            ->getManagerForClass(Slug::class)
            ->getRepository(Slug::class);

        $scopeCriteria = $this->scopeManager->getCriteria('web_content');

        return $repository->getSlugByUrlAndScopeCriteria($url, $scopeCriteria);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return string
     */
    protected function getResolvedUrl($routeName, array $routeParameters = [])
    {
        return '/' . $this->router->generate($routeName, $routeParameters, UrlGeneratorInterface::RELATIVE_PATH);
    }
}
