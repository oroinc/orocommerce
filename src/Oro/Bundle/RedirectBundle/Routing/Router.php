<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Router extends BaseRouter implements ContainerAwareInterface
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
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param MatchedUrlDecisionMaker $urlDecisionMaker
     */
    public function setMatchedUrlDecisionMaker(MatchedUrlDecisionMaker $urlDecisionMaker)
    {
        $this->urlDecisionMaker = $urlDecisionMaker;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerator()
    {
        if ($this->urlDecisionMaker->matches($this->context->getPathInfo())) {
            if (!$this->generator instanceof SluggableUrlGenerator) {
                $this->sluggableUrlGenerator = $this->container->get('oro_redirect.routing.sluggable_url_generator');
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
        if ($this->urlDecisionMaker->matches($this->context->getPathInfo())) {
            if (!$this->matcher instanceof SlugUrlMatcher) {
                $this->slugUrlMatcher = $this->container->get('oro_redirect.routing.slug_url_matcher');
                $this->slugUrlMatcher->setBaseMatcher(parent::getMatcher());
                $this->matcher = $this->slugUrlMatcher;
            }
        } elseif (!$this->matcher) {
            $this->matcher = parent::getMatcher();
        }

        return $this->matcher;
    }
}
