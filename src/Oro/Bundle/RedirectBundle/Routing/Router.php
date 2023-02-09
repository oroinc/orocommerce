<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extends the router to work with the storefront slugs.
 */
class Router extends BaseRouter
{
    private MatchedUrlDecisionMaker $urlDecisionMaker;
    private ContainerInterface $container;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'oro_redirect.routing.sluggable_url_generator' => SluggableUrlGenerator::class,
            'oro_redirect.routing.slug_url_matcher' => SlugUrlMatcher::class,
            'oro_locale.provider.current_localization' => CurrentLocalizationProvider::class,
        ]);
    }

    public function setUrlDecisionMaker(MatchedUrlDecisionMaker $urlDecisionMaker): void
    {
        $this->urlDecisionMaker = $urlDecisionMaker;
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerator()
    {
        if ($this->urlDecisionMaker->matches($this->context->getPathInfo())) {
            if (!$this->generator instanceof SluggableUrlGenerator) {
                $sluggableUrlGenerator = $this->container->get('oro_redirect.routing.sluggable_url_generator');
                $sluggableUrlGenerator->setBaseGenerator(parent::getGenerator());
                $this->generator = $sluggableUrlGenerator;
            }
        } elseif (!$this->generator) {
            $this->generator = parent::getGenerator();
        }

        return $this->generator;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcher()
    {
        if ($this->urlDecisionMaker->matches($this->context->getPathInfo())) {
            if (!$this->matcher instanceof SlugUrlMatcher) {
                $slugUrlMatcher = $this->container->get('oro_redirect.routing.slug_url_matcher');
                $slugUrlMatcher->setBaseMatcher(parent::getMatcher());
                $this->matcher = $slugUrlMatcher;
            }
        } elseif (!$this->matcher) {
            $this->matcher = parent::getMatcher();
        }

        return $this->matcher;
    }

    public function matchRequest(Request $request)
    {
        $attributes = parent::matchRequest($request);

        if ($attributes && ($usedSlug = $attributes['_used_slug'] ?? null)) {
            $currentLocalizationProvider = $this->container->get('oro_locale.provider.current_localization');
            $currentLocalizationProvider->setCurrentLocalization($usedSlug->getLocalization());
        }

        return $attributes;
    }
}
