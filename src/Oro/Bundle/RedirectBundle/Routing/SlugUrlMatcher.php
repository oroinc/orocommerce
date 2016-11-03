<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class SlugUrlMatcher implements RequestMatcherInterface
{
    /**
     * @var Router
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
     * @var RequestMatcherInterface
     */
    private $baseMatcher;

    /**
     * @param RequestMatcherInterface $baseMatcher
     * @param Router $router
     * @param ManagerRegistry $registry
     * @param FrontendHelper $frontendHelper
     * @param ScopeManager $scopeManager
     * @param boolean $installed
     * @param string $environment
     */
    public function __construct(
        RequestMatcherInterface $baseMatcher,
        Router $router,
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
        if ($this->matches($request)) {
            $attributes = $this->getAttributes($request);
        }

        if (!$attributes) {
            $attributes = $this->baseMatcher->matchRequest($request);
        }

        return $attributes;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function matches(Request $request)
    {
        if (!$this->installed) {
            return false;
        }

        if (!$this->frontendHelper->isFrontendRequest($request)) {
            return false;
        }

        if ($this->isSkippedUrl($request)) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isSkippedUrl(Request $request)
    {
        if (array_key_exists($this->environment, $this->skippedUrlPatterns)) {
            $url = $request->getPathInfo();
            foreach ($this->skippedUrlPatterns[$this->environment] as $pattern) {
                if (strpos($url, $pattern) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getAttributes(Request $request)
    {
        $attributes = [];

        $url = $this->getCleanUrl($request);
        $slug = $this->getSlug($url);
        if (!$slug) {
            return $attributes;
        }

        $routeName = $slug->getRouteName();
        $routeParameters = $slug->getRouteParameters();
        $routeData = $this->getRouteData($routeName, $routeParameters);

        if (array_key_exists('_controller', $routeData)) {
            $attributes['_route'] = $routeName;
            $attributes['_controller'] = $routeData['_controller'];
            $attributes = array_merge($attributes, $routeParameters);
            $attributes['_route_params'] = $routeParameters;
        }

        return $attributes;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getCleanUrl(Request $request)
    {
        $slugUrl = $request->getPathInfo();
        if ($slugUrl !== '/') {
            $slugUrl = rtrim($slugUrl, '/');
        }

        return $slugUrl;
    }

    /**
     * @param string $url
     * @return Slug|null
     */
    protected function getSlug($url)
    {
        /** @var SlugRepository $em */
        $repository = $this->registry
            ->getManagerForClass(Slug::class)
            ->getRepository(Slug::class);

        $scopeCriteria = $this->scopeManager->getCriteria('web_content');

        return $repository->getSlugByUrlAndScopeCriteria($url, $scopeCriteria);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return array
     */
    protected function getRouteData($routeName, array $routeParameters = [])
    {
        $generator = $this->router->getGenerator();
        $matcher = $this->router->getMatcher();
        $routeData = $matcher->match(
            '/' . $generator->generate($routeName, $routeParameters, UrlGeneratorInterface::RELATIVE_PATH)
        );

        return $routeData;
    }
}
