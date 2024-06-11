<?php

namespace Oro\Bundle\RedirectBundle\Factory;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Creates a new {@see Request} based on a provided {@see Slug}.
 */
class SubRequestFactory
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    /**
     * @param Request $request
     * @param string $url
     * @param array|null $getParameters GET parameters
     * @param array|null $postParameters POST parameters
     * @param array|null $requestAttributes Additional request attributes
     *
     * @return Request
     */
    public function createSubRequest(
        Request $request,
        string $url,
        ?array $getParameters = null,
        ?array $postParameters = null,
        ?array $requestAttributes = null
    ): Request {
        $pathInfo = UrlUtil::getPathInfo($url, $this->router->getContext()->getBaseUrl());

        try {
            $routeInfo = $this->router->match($pathInfo);

            $newRequest = $request->duplicate(
                $getParameters,
                $postParameters,
                (array)$requestAttributes + $routeInfo + $request->attributes->all()
            );

            unset($routeInfo['_route'], $routeInfo['_controller']);
            $newRequest->attributes->set('_route_params', $routeInfo);
        } catch (ResourceNotFoundException $e) {
            $message = sprintf(
                'No route found for "%s %s"',
                $request->getMethod(),
                $request->getUriForPath($request->getPathInfo())
            );

            throw new NotFoundHttpException($message, $e);
        } catch (MethodNotAllowedException $e) {
            $message = sprintf(
                'No route found for "%s %s": Method Not Allowed (Allow: %s)',
                $request->getMethod(),
                $request->getUriForPath($request->getPathInfo()),
                implode(', ', $e->getAllowedMethods())
            );

            throw new MethodNotAllowedHttpException($e->getAllowedMethods(), $message, $e);
        }

        return $newRequest;
    }
}
