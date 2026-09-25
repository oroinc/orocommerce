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
 * Completes the response of an embedded fragment for the consumer: names the target page path in a header and,
 * on a GET, adds the plain-text page title, the session flash messages and, on the first render of a fragment,
 * the metadata the embedded runtime needs to boot to the layout JSON body.
 */
class EmbeddedFragmentResponseDecorator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string PATH_HEADER = 'X-Oro-Embedded-Path';
    public const string FLASH_MESSAGES_KEY = 'flashMessages';
    public const string TITLE_KEY = 'title';

    private const string CONTENT_TYPE_HEADER = 'Content-Type';

    public function __construct(
        private readonly EmbeddedRuntimeBootProvider $runtimeBootProvider,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
        $this->logger = new NullLogger();
    }

    public function decorate(Request $request, Response $response, EmbeddedFragmentTarget $target): void
    {
        $response->headers->set(self::PATH_HEADER, $target->path);

        if ($request->isMethod(Request::METHOD_GET)) {
            $this->addEmbeddedMetadataToBody($request, $response, $target->block);
        }
    }

    private function addEmbeddedMetadataToBody(Request $request, Response $response, string $block): void
    {
        $payload = json_decode((string) $response->getContent(), true);
        if (!\is_array($payload)) {
            $this->logger->debug('Embedded fragment body was not layout JSON, metadata was not added.', [
                'block' => $block,
                'status_code' => $response->getStatusCode(),
                'content_type' => $response->headers->get(self::CONTENT_TYPE_HEADER),
            ]);

            return;
        }

        $payload[self::TITLE_KEY] = $this->resolvePageTitle($payload);
        $payload[self::FLASH_MESSAGES_KEY] = $this->takeFlashMessages($request);

        if (!$this->embeddedRequestChecker->isFragmentReload()) {
            $payload = array_merge($payload, $this->runtimeBootProvider->getBootMetadata($request, $block));
        }

        $this->replaceBody($response, $payload);
    }

    /**
     * Keeps the escaping of the layout JsonResponse, whose hex-escaped markup is safe to inline into a script.
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
        $renderedTitleBlock = (string) ($payload[self::TITLE_KEY] ?? '');

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
