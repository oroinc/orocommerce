<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Extends the router to work with the storefront slugs.
 */
class Router extends BaseRouter
{
    private MatchedUrlDecisionMaker $urlDecisionMaker;
    private ContainerInterface $container;

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'oro_redirect.routing.sluggable_url_generator' => SluggableUrlGenerator::class,
            'oro_redirect.routing.slug_url_matcher' => SlugUrlMatcher::class
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

    #[\Override]
    public function getGenerator(): UrlGeneratorInterface
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

    #[\Override]
    public function getMatcher(): UrlMatcherInterface|RequestMatcherInterface
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
}
