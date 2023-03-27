<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceModeState;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SlugEntityFinder;
use Oro\Component\Routing\UrlUtil;
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
    private const MATCH_SYSTEM = 'system';
    private const MATCH_MAINTENANCE = 'maintenance';
    private const MATCH_SLUG = 'slug';

    private RouterInterface $router;
    private MatchedUrlDecisionMaker $matchedUrlDecisionMaker;
    private SlugEntityFinder $slugEntityFinder;
    private MaintenanceModeState $maintenanceModeState;

    private RequestMatcherInterface|UrlMatcherInterface $baseMatcher;
    private array $matchSlugsFirst = [];
    private ?RequestContext $context = null;
    private array $slugByUrl = [];
    private array $slugBySlugPrototype = [];

    public function __construct(
        RouterInterface $router,
        MatchedUrlDecisionMaker $matchedUrlDecisionMaker,
        SlugEntityFinder $slugEntityFinder,
        MaintenanceModeState $maintenanceModeState
    ) {
        $this->router = $router;
        $this->matchedUrlDecisionMaker = $matchedUrlDecisionMaker;
        $this->slugEntityFinder = $slugEntityFinder;
        $this->maintenanceModeState = $maintenanceModeState;
    }

    public function setBaseMatcher(RequestMatcherInterface|UrlMatcherInterface $baseMatcher): void
    {
        $this->baseMatcher = $baseMatcher;
    }

    public function addUrlToMatchSlugFirst(string $pathinfo): void
    {
        $this->matchSlugsFirst[$pathinfo] = true;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext()
    {
        return $this->context ?? $this->baseMatcher->getContext();
    }

    /**
     * {@inheritDoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
        if ($this->baseMatcher instanceof RequestContextAwareInterface) {
            $this->baseMatcher->setContext($context);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function matchRequest(Request $request)
    {
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);
        $this->setContext($requestContext);

        $pathinfo = $request->getPathInfo();
        $matchers = [
            self::MATCH_SYSTEM => function () use ($request) {
                try {
                    return $this->baseMatcher->matchRequest($request);
                } catch (ResourceNotFoundException $e) {
                    return [];
                }
            },
            self::MATCH_MAINTENANCE => $this->getMaintenanceMatcher(),
            self::MATCH_SLUG => $this->getSlugMatcher($pathinfo)
        ];

        return $this->resolveAttributes($matchers, $pathinfo);
    }

    /**
     * {@inheritDoc}
     */
    public function match($pathinfo)
    {
        $matchers = [
            self::MATCH_SYSTEM => function () use ($pathinfo) {
                try {
                    return $this->baseMatcher->match($pathinfo);
                } catch (ResourceNotFoundException $e) {
                    return [];
                }
            },
            self::MATCH_MAINTENANCE => $this->getMaintenanceMatcher(),
            self::MATCH_SLUG => $this->getSlugMatcher($pathinfo)
        ];

        return $this->resolveAttributes($matchers, $pathinfo);
    }

    private function getMaintenanceMatcher(): callable
    {
        return function () {
            // prevents http not found exception for slugged urls when maintenance mode is enabled
            return $this->maintenanceModeState->isOn()
                ? ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index']
                : [];
        };
    }

    private function getSlugMatcher(string $pathinfo): callable
    {
        return function () use ($pathinfo) {
            if ($this->matchedUrlDecisionMaker->matches($pathinfo)) {
                return $this->getAttributesWithContext($pathinfo);
            }

            return [];
        };
    }

    private function getMatchersOrder(string $pathinfo): array
    {
        if (!empty($this->matchSlugsFirst[$pathinfo])) {
            return [self::MATCH_MAINTENANCE, self::MATCH_SLUG, self::MATCH_SYSTEM];
        }

        return [self::MATCH_SYSTEM, self::MATCH_MAINTENANCE, self::MATCH_SLUG];
    }

    private function resolveAttributes(array $matchers, string $pathinfo): array
    {
        $matchersOrder = $this->getMatchersOrder($pathinfo);
        foreach ($matchersOrder as $matcher) {
            $attributes = \call_user_func($matchers[$matcher]);
            if ($attributes) {
                return $attributes;
            }
        }

        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }

    /**
     * Get URL attributes with context URL attributes.
     *
     * Both may be sluggable URLs or system URLs, so rematching is required to cover both cases.
     * "_context_url_attributes" key stores an array attributes of all contexts starting from latest.
     */
    private function getAttributesWithContext(string $pathinfo): array
    {
        $delimiter = '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/';
        if (!str_contains($pathinfo, $delimiter)) {
            return $this->getAttributes($pathinfo);
        }

        [$contextUrl, $url] = explode($delimiter, $pathinfo, 2);
        $contextAttributes = $this->match($contextUrl);
        $urlAttributes = $this->matchContextAwareUrl($url);
        if ($urlAttributes) {
            $urlAttributes['_context_url_attributes'][] = $contextAttributes;
        }

        return $urlAttributes;
    }

    private function matchContextAwareUrl(string $url): array
    {
        $attributes = $this->getAttributesBySlug($this->findSlugEntityBySlugPrototype($url));
        if (!$attributes) {
            $attributes = $this->match('/' . $url);
        }

        return $attributes;
    }

    private function getAttributes(string $pathinfo): array
    {
        return $this->getAttributesBySlug($this->findSlugEntityByUrl($pathinfo));
    }

    private function getAttributesBySlug(?Slug $slug): array
    {
        if (null === $slug) {
            return [];
        }

        $routeName = $slug->getRouteName();
        $routeParameters = $slug->getRouteParameters();

        $resolvedUrl = UrlUtil::getPathInfo(
            $this->router->generate($routeName, $routeParameters),
            $this->getContext()->getBaseUrl()
        );
        $routeData = $this->router->match(parse_url($resolvedUrl, PHP_URL_PATH));

        $attributes = [];
        if (\array_key_exists('_controller', $routeData)) {
            $attributes['_route'] = $routeName;
            $attributes['_controller'] = $routeData['_controller'];
            $attributes = array_merge($attributes, $routeParameters);
            $attributes['_route_params'] = $routeParameters;
            $attributes['_resolved_slug_url'] = $resolvedUrl;
            $attributes['_used_slug'] = $slug;
        }

        return $attributes;
    }

    private function findSlugEntityByUrl(string $url): ?Slug
    {
        $url = $this->getCleanUrl($url);
        if (\array_key_exists($url, $this->slugByUrl)) {
            return $this->slugByUrl[$url];
        }

        $slug = $this->slugEntityFinder->findSlugEntityByUrl($url);
        $this->slugByUrl[$url] = $slug;

        return $slug;
    }

    private function findSlugEntityBySlugPrototype(string $slugPrototype): ?Slug
    {
        $slugPrototype = $this->getCleanUrl($slugPrototype);
        if (\array_key_exists($slugPrototype, $this->slugBySlugPrototype)) {
            return $this->slugBySlugPrototype[$slugPrototype];
        }

        $slug = $this->slugEntityFinder->findSlugEntityBySlugPrototype($slugPrototype);
        $this->slugBySlugPrototype[$slugPrototype] = $slug;

        return $slug;
    }

    private function getCleanUrl(string $url): string
    {
        if ('/' !== $url) {
            $url = rtrim($url, '/');
        }

        return $url;
    }
}
