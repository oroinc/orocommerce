<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentUrlResolver;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves /embedded/fragment/{fragment}: rewrites the main request to the target page of the fragment before routing.
 */
final class EmbeddedFragmentRequestListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $allowedMethods = [
        Request::METHOD_HEAD,
        Request::METHOD_GET,
        Request::METHOD_POST,
        Request::METHOD_PUT,
    ];

    /**
     * @param string $fragmentPath The path prefix of the fragment endpoint, without a trailing slash.
     */
    public function __construct(
        private readonly EmbeddedFragmentUrlResolver $embeddedFragmentUrlResolver,
        private readonly string $fragmentPath,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @throws MethodNotAllowedHttpException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $fragmentName = $this->getFragmentName($request);
        if ($fragmentName === null) {
            return;
        }

        if (!in_array($request->getMethod(), $this->allowedMethods, true)) {
            throw new MethodNotAllowedHttpException(
                $this->allowedMethods,
                sprintf('The embedded fragment does not support the %s method.', $request->getMethod())
            );
        }

        $fragmentUrl = $this->embeddedFragmentUrlResolver->resolveFragmentUrl(
            $fragmentName,
            $this->withoutInternalParameters($request->query->all()),
            [EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request]
        );

        $this->logger->info('Embedded fragment request rewritten to "{fragmentUrl}".', [
            'fragmentName' => $fragmentName,
            'fragmentUrl' => $fragmentUrl,
            'requestMethod' => $request->getMethod(),
        ]);

        $this->rewriteRequest($request, $fragmentName, $fragmentUrl);
    }

    private function getFragmentName(Request $request): ?string
    {
        $matches = [];
        if (
            !preg_match(
                '#^' . preg_quote($this->fragmentPath, '#') . '/([^/]+)$#D',
                rawurldecode($request->getPathInfo()),
                $matches
            )
        ) {
            return null;
        }

        return $matches[1] ?? null;
    }

    /**
     * @param array<string|int, mixed> $queryParameters
     *
     * @return array<string|int, mixed>
     */
    private function withoutInternalParameters(array $queryParameters): array
    {
        return array_filter(
            $queryParameters,
            static fn (string|int $name): bool => !\is_string($name) || !str_starts_with($name, '_'),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function rewriteRequest(Request $request, string $fragmentName, string $fragmentUrl): void
    {
        $queryString = (string)parse_url($fragmentUrl, PHP_URL_QUERY);
        $query = [];
        parse_str($queryString, $query);

        $attributes = $request->attributes->all();
        unset($attributes['_controller']);
        $attributes[EmbeddedRequestChecker::EMBEDDED_FRAGMENT_NAME] = $fragmentName;

        $server = $request->server->all();
        $server['REQUEST_URI'] = $fragmentUrl;
        $server['QUERY_STRING'] = $queryString;

        $files = $request->files;

        $request->initialize(
            $query,
            $request->request->all(),
            $attributes,
            $request->cookies->all(),
            [],
            $server,
            $request->getContent()
        );
        $request->files = $files;
    }
}
