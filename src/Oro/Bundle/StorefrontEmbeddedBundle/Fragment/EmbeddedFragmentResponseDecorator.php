<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRuntimeBootProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * Completes the response of an embedded fragment with the data that the consumer needs.
 */
class EmbeddedFragmentResponseDecorator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EmbeddedRuntimeBootProvider $runtimeBootProvider,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
        $this->logger = new NullLogger();
    }

    public function decorate(Request $request, Response $response, EmbeddedFragmentTarget $target): void
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            $this->addEmbeddedMetadataToBody($request, $response, $target);
        }
    }

    private function addEmbeddedMetadataToBody(
        Request $request,
        Response $response,
        EmbeddedFragmentTarget $target
    ): void {
        $payload = json_decode((string) $response->getContent(), true);
        if (!\is_array($payload)) {
            $this->logger->debug('Embedded fragment body was not layout JSON, metadata was not added.', [
                'block' => $target->block,
                'status_code' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type'),
            ]);

            return;
        }

        $payload['title'] = $this->resolvePageTitle($payload);
        $payload['flashMessages'] = $this->takeFlashMessages($request);
        $payload['subtreeUrl'] = $target->path;

        if (!$this->embeddedRequestChecker->isEmbeddedRequestReload()) {
            $payload = array_merge($payload, $this->runtimeBootProvider->getBootMetadata());
        }

        $this->replaceBody($response, $payload);
    }

    /**
     * Keeps the hex escaping of the layout JsonResponse, which makes the markup safe to inline in a script.
     *
     * @param array<string, mixed> $payload
     */
    private function replaceBody(Response $response, array $payload): void
    {
        if ($response instanceof JsonResponse) {
            $response->setData($payload);

            return;
        }

        $response->setContent(
            json_encode($payload, JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolvePageTitle(array $payload): string
    {
        $renderedTitleBlock = (string) ($payload['title'] ?? '');

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
}
