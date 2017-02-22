<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
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
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerator()
    {
        if ($this->frontendHelper->isFrontendRequest()) {
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
        if ($this->frontendHelper->isFrontendRequest()) {
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
