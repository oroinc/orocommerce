<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForwardListener
{
    const CONTROLLER_SKIP_404 = 'controller_skip_404';

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
     * @var string
     */
    protected $acceptableSlugUrl;

    /**
     * @param Router $router
     * @param ManagerRegistry $registry
     * @param FrontendHelper $frontendHelper
     * @param ScopeManager $scopeManager
     * @param boolean $installed
     * @param string $environment
     */
    public function __construct(
        Router $router,
        ManagerRegistry $registry,
        FrontendHelper $frontendHelper,
        ScopeManager $scopeManager,
        $installed,
        $environment
    ) {
        $this->router = $router;
        $this->registry = $registry;
        $this->installed = $installed;
        $this->scopeManager = $scopeManager;
        $this->frontendHelper = $frontendHelper;
        $this->environment = $environment;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestPrepare(GetResponseEvent $event)
    {
        if (!$this->isAvailable($event)) {
            return;
        }

        $request = $event->getRequest();
        $slugUrl = $this->getSlugUrl($request);

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroRedirectBundle:Slug');
        $slug = $em->getRepository('OroRedirectBundle:Slug')->findOneBy(['url' => $slugUrl]);
        if ($slug) {
            // Add attributes for request for skip the RouterListener 404 generation
            $request->attributes->add(['_controller' => self::CONTROLLER_SKIP_404]);
            $this->acceptableSlugUrl = $slugUrl;
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->isAvailable($event) && $this->acceptableSlugUrl) {
            $this->forwardRequest($event->getRequest(), $this->acceptableSlugUrl);
        }
    }

    /**
     * @param GetResponseEvent $event
     * @return bool
     */
    protected function isAvailable(GetResponseEvent $event)
    {
        if (!$this->installed) {
            return false;
        }

        $request = $event->getRequest();

        if ($request->attributes->has('_controller')
            && $request->attributes->get('_controller') != self::CONTROLLER_SKIP_404
            || $event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST
        ) {
            return false;
        }

        if ($this->isSkippedUrl($request)) {
            return false;
        }

        return true;
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
     * @param Request $request
     * @return bool
     */
    protected function isSkippedUrl(Request $request)
    {
        if (!$this->frontendHelper->isFrontendRequest($request)) {
            return true;
        }

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
     * @return string
     */
    protected function getSlugUrl(Request $request)
    {
        $slugUrl = $request->getPathInfo();
        if ($slugUrl !== '/') {
            $slugUrl = rtrim($slugUrl, '/');
        }

        return $slugUrl;
    }

    /**
     * @param Request $request
     * @param string $slugUrl
     */
    protected function forwardRequest(Request $request, $slugUrl)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(Slug::class);
        $qb = $em->createQueryBuilder();
        $qb->select('slug')
            ->from(Slug::class, 'slug')
            ->join('slug.scopes', 'scopes', Join::WITH)
            ->where($qb->expr()->eq('slug.url', ':url'))
            ->setParameter('url', $slugUrl)
            ->setMaxResults(1);

        $scopeCriteria = $this->scopeManager->getCriteria('web_content');
        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');

        $slug = $qb->getQuery()->getOneOrNullResult();
        if (!$slug) {
            throw new NotFoundHttpException();
        }

        $routeName = $slug->getRouteName();
        $routeParameters = $slug->getRouteParameters();

        $generator = $this->router->getGenerator();
        $matcher = $this->router->getMatcher();
        $route = $matcher->match(
            '/' . $generator->generate($routeName, $routeParameters, UrlGeneratorInterface::RELATIVE_PATH)
        );

        if (!array_key_exists('_controller', $route)) {
            throw new NotFoundHttpException();
        }

        $parameters = [];
        $parameters['_route'] = $routeName;
        $parameters['_controller'] = $route['_controller'];
        $parameters = array_merge($parameters, $routeParameters);
        $parameters['_route_params'] = $routeParameters;

        $request->attributes->add($parameters);
    }
}
