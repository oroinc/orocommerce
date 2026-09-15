<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRuntimeBootProvider;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Serves a curated embedded layout block by id, so an external consumer (e.g. the Nuxt storefront) can
 * request a storefront fragment without knowing which page URL it lives on. Forwards an internal
 * sub-request with `layout_block_ids[]` (LayoutListener then renders only that subtree as JSON); the
 * target controller runs with the caller's session, and its ACL attributes are checked here because the
 * platform enforces them only for a main request. POST forwards a form submit the same way, so the response
 * is either the re-rendered block (validation errors) or the target's save redirect.
 */
class EmbeddedFragmentController extends AbstractController
{
    private const string CONFIG_SCRIPTS_BLOCK_ID = 'layout_js_modules_config';
    private const string TITLE_BLOCK_ID = 'title';
    private const string LAYOUT_BLOCK_IDS_PARAM = 'layout_block_ids';
    private const string PATH_HEADER = 'X-Oro-Embedded-Path';
    private const string FLASH_MESSAGES_KEY = 'flashMessages';

    public function __construct(
        private readonly ClassAuthorizationChecker $classAuthorizationChecker,
        private readonly ControllerClassProvider $controllerClassProvider,
        private readonly EmbeddedRuntimeBootProvider $runtimeBootProvider,
    ) {
    }

    #[Route(
        path: '/{block}',
        name: 'oro_storefront_embedded_fragment',
        requirements: ['block' => 'embedded_\w+'],
        methods: ['GET', 'POST']
    )]
    public function fragmentAction(string $block, Request $request): Response
    {
        $routeParameters = $request->query->all();
        $targetUrl = $this->resolveTargetUrl($block, $routeParameters);
        $targetPath = $this->withoutBaseUrl((string) parse_url($targetUrl, PHP_URL_PATH));
        $attributes = $this->resolveTargetAttributes($block, $targetPath);
        $this->denyAccessUnlessTargetPageIsGranted($block, $attributes['_route']);
        $attributes[EmbeddedContextConfigurator::REQUEST_ATTRIBUTE] = true;

        $response = $this->renderTargetPageSubtrees($request, $attributes, $routeParameters, $targetUrl, $block);

        $response->headers->set(self::PATH_HEADER, $targetPath);

        if ($request->isMethod('GET')) {
            $this->addEmbeddedMetadataToBody($request, $response, $block);
        }

        return $response;
    }

    private function denyAccessUnlessTargetPageIsGranted(string $block, string $routeName): void
    {
        $controller = $this->controllerClassProvider->getControllers()[$routeName] ?? null;
        if (null === $controller) {
            throw $this->createNotFoundException(
                sprintf('Cannot resolve the controller behind embedded block "%s".', $block)
            );
        }

        [$controllerClass, $controllerMethod] = $controller;
        if (!$this->classAuthorizationChecker->isClassMethodGranted($controllerClass, $controllerMethod)) {
            throw $this->createAccessDeniedException(
                sprintf('Access denied to the page behind embedded block "%s".', $block)
            );
        }
    }

    private function renderTargetPageSubtrees(
        Request $request,
        array $attributes,
        array $routeParameters,
        string $targetUrl,
        string $block
    ): Response {
        $subRequest = $request->duplicate(
            [self::LAYOUT_BLOCK_IDS_PARAM => $this->blockIdsToRender($request, $block)]
                + array_diff_key($routeParameters, $attributes),
            null,
            $attributes
        );
        $subRequest->server->set('REQUEST_URI', $targetUrl);
        $subRequest->server->set('QUERY_STRING', (string) parse_url($targetUrl, PHP_URL_QUERY));

        return $this->container->get('http_kernel')
            ->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }

    private function blockIdsToRender(Request $request, string $block): array
    {
        $blockIds = [$block];

        if (!$this->isFragmentReload($request)) {
            $blockIds[] = self::CONFIG_SCRIPTS_BLOCK_ID;
        }

        if ($request->isMethod('GET')) {
            $blockIds[] = self::TITLE_BLOCK_ID;
        }

        return $blockIds;
    }

    private function isFragmentReload(Request $request): bool
    {
        return (bool) $request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER);
    }

    private function resolveTargetUrl(string $block, array $routeParameters): string
    {
        $routeName = $this->resolveTargetRouteName($block);

        try {
            return $this->getRouter()->generate($routeName, $routeParameters);
        } catch (MissingMandatoryParametersException | InvalidParameterException $exception) {
            throw new BadRequestHttpException(
                sprintf('Invalid parameters for embedded block "%s": %s', $block, $exception->getMessage()),
                $exception
            );
        } catch (RouteNotFoundException $exception) {
            throw $this->createNotFoundException(
                sprintf('Embedded block "%s" points at unknown route "%s".', $block, $routeName),
                $exception
            );
        }
    }

    private function resolveTargetRouteName(string $block): string
    {
        $routeName = $this->getParameter('oro_storefront_embedded.blocks')[$block] ?? null;
        if (!$routeName) {
            throw $this->createNotFoundException(sprintf('Unknown embedded block "%s".', $block));
        }

        return $routeName;
    }

    private function withoutBaseUrl(string $path): string
    {
        $baseUrl = $this->getRouter()->getContext()->getBaseUrl();

        return $baseUrl !== '' && str_starts_with($path, $baseUrl) ? substr($path, \strlen($baseUrl)) : $path;
    }

    private function resolveTargetAttributes(string $block, string $targetPath): array
    {
        try {
            return $this->getRouter()->match($targetPath);
        } catch (ResourceNotFoundException | MethodNotAllowedException $exception) {
            throw $this->createNotFoundException(
                sprintf('Cannot resolve target route for embedded block "%s".', $block),
                $exception
            );
        }
    }

    private function addEmbeddedMetadataToBody(Request $request, Response $response, string $block): void
    {
        $payload = json_decode((string) $response->getContent(), true);
        if (!\is_array($payload)) {
            return;
        }

        $payload[self::TITLE_BLOCK_ID] = $this->pageTitleOf($payload);
        $payload[self::FLASH_MESSAGES_KEY] = $this->takeFlashMessages($request);

        if (!$this->isFragmentReload($request)) {
            $payload = array_merge($payload, $this->runtimeBootProvider->getBootMetadata($request, $block));
        }

        $response->setContent(json_encode($payload));
    }

    private function pageTitleOf(array $payload): string
    {
        $renderedTitleBlock = (string) ($payload[self::TITLE_BLOCK_ID] ?? '');

        return trim(html_entity_decode(strip_tags($renderedTitleBlock), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @return array<string, string[]>
     */
    private function takeFlashMessages(Request $request): array
    {
        $session = $request->hasSession() ? $request->getSession() : null;

        return $session instanceof FlashBagAwareSessionInterface ? $session->getFlashBag()->all() : [];
    }

    private function getRouter(): RouterInterface
    {
        return $this->container->get('router');
    }
}
