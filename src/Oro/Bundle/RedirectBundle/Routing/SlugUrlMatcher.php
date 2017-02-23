<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Decorates default URL matcher. Perform URL matching with decorated system matcher.
 * If default matcher unable to resolve URL detects route name and route parameter by slug entity.
 * Able to decorate both types of base matcher: RequestMatcherInterface, UrlMatcherInterface
 */
class SlugUrlMatcher implements RequestMatcherInterface, UrlMatcherInterface
{
    const MATCH_SYSTEM = 'system';
    const MATCH_SLUG = 'slug';

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var SlugRepository
     */
    protected $slugRepository;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var RequestMatcherInterface|UrlMatcherInterface
     */
    protected $baseMatcher;

    /**
     * @var MatchedUrlDecisionMaker
     */
    protected $matchedUrlDecisionMaker;

    /**
     * @var array
     */
    protected $matchSlugsFirst = [];

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var ScopeCriteria
     */
    protected $scopeCriteria;

    /**
     * @param RouterInterface $router
     * @param SlugRepository $slugRepository
     * @param ScopeManager $scopeManager
     * @param MatchedUrlDecisionMaker $matchedUrlDecisionMaker
     */
    public function __construct(
        RouterInterface $router,
        SlugRepository $slugRepository,
        ScopeManager $scopeManager,
        MatchedUrlDecisionMaker $matchedUrlDecisionMaker
    ) {
        $this->router = $router;
        $this->slugRepository = $slugRepository;
        $this->scopeManager = $scopeManager;
        $this->matchedUrlDecisionMaker = $matchedUrlDecisionMaker;
    }

    public function setBaseMatcher($baseMatcher)
    {
        $this->baseMatcher = $baseMatcher;
    }

    /**
     * @param $url
     */
    public function addUrlToMatchSlugFirst($url)
    {
        $this->matchSlugsFirst[$url] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        $matchersOrder = $this->getMatchersOrder($request->getPathInfo());

        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);
        $this->setContext($requestContext);

        $matchers = [
            self::MATCH_SYSTEM => function () use ($request) {
                try {
                    return $this->baseMatcher->matchRequest($request);
                } catch (ResourceNotFoundException $e) {
                    return [];
                }
            },
            self::MATCH_SLUG => function () use ($request) {
                $url = $request->getPathInfo();
                if ($this->matchedUrlDecisionMaker->matches($url)) {
                    return $this->getAttributesWithContext($url);
                }

                return [];
            }
        ];

        return $this->resolveAttributes($matchers, $matchersOrder, $request->getPathInfo());
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $matchersOrder = $this->getMatchersOrder($pathinfo);
        $matchers = [
            self::MATCH_SYSTEM => function () use ($pathinfo) {
                try {
                    return $this->baseMatcher->match($pathinfo);
                } catch (ResourceNotFoundException $e) {
                    return [];
                }
            },
            self::MATCH_SLUG => function () use ($pathinfo) {
                if ($this->matchedUrlDecisionMaker->matches($pathinfo)) {
                    return $this->getAttributesWithContext($pathinfo);
                }

                return [];
            }
        ];

        return $this->resolveAttributes($matchers, $matchersOrder, $pathinfo);
    }

    /**
     * @param array $matchers
     * @param array $matchersOrder
     * @param string $url
     * @return array
     */
    protected function resolveAttributes(array $matchers, array $matchersOrder, $url)
    {
        foreach ($matchersOrder as $matcher) {
            if ($attributes = call_user_func($matchers[$matcher])) {
                return $attributes;
            }
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $url));
    }

    /**
     * @param string $url
     * @return array
     */
    protected function getMatchersOrder($url)
    {
        if (!empty($this->matchSlugsFirst[$url])) {
            return [self::MATCH_SLUG, self::MATCH_SYSTEM];
        }

        return [self::MATCH_SYSTEM, self::MATCH_SLUG];
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;

        if ($this->baseMatcher instanceof RequestContextAwareInterface) {
            $this->baseMatcher->setContext($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get URL attributes with context URL attributes.
     *
     * Both may be sluggable URLs or system URLs, so rematching is required to cover both cases.
     * "_context_url_attributes" key stores an array attributes of all contexts starting from latest.
     *
     * @param string $url
     * @return array
     */
    protected function getAttributesWithContext($url)
    {
        $delimiter = '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/';
        if (strpos($url, $delimiter) !== false) {
            list($contextUrl, $url) = explode($delimiter, $url, 2);

            $contextAttributes = $this->match($contextUrl);
            $urlAttributes = $this->matchContextAwareUrl($url);
            if ($urlAttributes) {
                $urlAttributes['_context_url_attributes'][] = $contextAttributes;
            }

            return $urlAttributes;
        } else {
            return $this->getAttributes($url);
        }
    }

    /**
     * @param string $url
     * @return array
     */
    protected function matchContextAwareUrl($url)
    {
        $slug = $this->getSlugEntityBySlug($url);
        $attributes = $this->getAttributesBySlug($slug);
        if (!$attributes) {
            $attributes = $this->match('/' . $url);
        }

        return $attributes;
    }

    /**
     * @param string $url
     * @return array
     */
    protected function getAttributes($url)
    {
        return $this->getAttributesBySlug($this->getSlugEntityByUrl($url));
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
    protected function getSlugEntityByUrl($url)
    {
        $url = $this->getCleanUrl($url);

        return $this->slugRepository->getSlugByUrlAndScopeCriteria($url, $this->getScopeCriteria());
    }

    /**
     * @param string $url
     * @return Slug|null
     */
    protected function getSlugEntityBySlug($url)
    {
        $url = $this->getCleanUrl($url);

        return $this->slugRepository->getSlugBySlugPrototypeAndScopeCriteria($url, $this->getScopeCriteria());
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return string
     */
    protected function getResolvedUrl($routeName, array $routeParameters = [])
    {
        $baseUrl = $this->getContext()->getBaseUrl();
        $url = $this->router->generate($routeName, $routeParameters);

        if ($baseUrl && strpos($url, $baseUrl) === 0) {
            $url = substr($url, strlen($baseUrl));
        }

        return '/' . ltrim($url, '/');
    }

    /**
     * @return ScopeCriteria
     */
    protected function getScopeCriteria()
    {
        if (!$this->scopeCriteria) {
            $this->scopeCriteria = $this->scopeManager->getCriteria('web_content');
        }

        return $this->scopeCriteria;
    }

    /**
     * @param Slug $slug
     * @return array
     */
    protected function getAttributesBySlug(Slug $slug = null)
    {
        $attributes = [];
        if (!$slug) {
            return $attributes;
        }

        $routeName = $slug->getRouteName();
        $routeParameters = $slug->getRouteParameters();

        $resolvedUrl = $this->getResolvedUrl($routeName, $routeParameters);
        $routeData = $this->router->match(parse_url($resolvedUrl, PHP_URL_PATH));

        if (array_key_exists('_controller', $routeData)) {
            $attributes['_route'] = $routeName;
            $attributes['_controller'] = $routeData['_controller'];
            $attributes = array_merge($attributes, $routeParameters);
            $attributes['_route_params'] = $routeParameters;
            $attributes['_resolved_slug_url'] = $resolvedUrl;
            $attributes['_used_slug'] = $slug;
        }

        return $attributes;
    }
}
