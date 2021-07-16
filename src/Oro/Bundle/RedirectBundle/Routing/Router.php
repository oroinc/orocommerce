<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;

/**
 * Router for storefront URL's
 */
class Router extends BaseRouter
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var SluggableUrlGenerator
     */
    private $sluggableUrlGenerator;

    /**
     * @var SlugUrlMatcher
     */
    private $slugUrlMatcher;

    /**
     * @var MatchedUrlDecisionMaker
     */
    private $urlDecisionMaker;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                MatchedUrlDecisionMaker::class,
                SluggableUrlGenerator::class,
                SlugUrlMatcher::class,
            ]
        );
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerator()
    {
        if ($this->matches($this->context->getPathInfo())) {
            if (!$this->generator instanceof SluggableUrlGenerator) {
                $this->sluggableUrlGenerator = $this->container->get(SluggableUrlGenerator::class);
                $this->sluggableUrlGenerator->setBaseGenerator(parent::getGenerator());
                $this->generator = $this->sluggableUrlGenerator;
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
        if ($this->matches($this->context->getPathInfo())) {
            if (!$this->matcher instanceof SlugUrlMatcher) {
                $this->slugUrlMatcher = $this->container->get(SlugUrlMatcher::class);
                $this->slugUrlMatcher->setBaseMatcher(parent::getMatcher());
                $this->matcher = $this->slugUrlMatcher;
            }
        } elseif (!$this->matcher) {
            $this->matcher = parent::getMatcher();
        }

        return $this->matcher;
    }

    private function matches(string $pathInfo): bool
    {
        if (!$this->urlDecisionMaker) {
            $this->urlDecisionMaker = $this->container->get(MatchedUrlDecisionMaker::class);
        }

        return $this->urlDecisionMaker->matches($pathInfo);
    }
}
