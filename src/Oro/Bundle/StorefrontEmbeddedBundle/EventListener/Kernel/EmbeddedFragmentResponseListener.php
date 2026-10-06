<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Lets the EmbeddedFragmentResponseEvent listeners change the layout JSON of a successful embedded fragment response.
 */
class EmbeddedFragmentResponseListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
        $this->logger = new NullLogger();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (
            !$event->isMainRequest()
            || !$this->embeddedRequestChecker->isEmbeddedFragmentRequest($request)
        ) {
            return;
        }

        $response = $event->getResponse();
        if (!$response->isSuccessful()) {
            return;
        }

        $this->addMetadataToBody($request, $response);
    }

    private function addMetadataToBody(Request $request, Response $response): void
    {
        $fragmentName = (string) $this->embeddedRequestChecker->getEmbeddedFragmentName($request);
        try {
            $content = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Embedded fragment body could not be decoded, metadata was not added.', [
                'fragment' => $fragmentName,
                'status_code' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type'),
                'exception' => $exception,
            ]);

            return;
        }

        if (!\is_array($content)) {
            $this->logger->debug('Embedded fragment body was not layout JSON, metadata was not added.', [
                'fragment' => $fragmentName,
                'status_code' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type'),
            ]);

            return;
        }

        $responseEvent = new EmbeddedFragmentResponseEvent(
            $fragmentName,
            [EmbeddedFragmentResponseEvent::CONTEXT_REQUEST => $request],
            $content
        );
        $this->eventDispatcher->dispatch($responseEvent);

        try {
            $this->replaceBody($response, $responseEvent->getContent());
        } catch (\InvalidArgumentException | \JsonException $exception) {
            $this->logger->error(
                'Embedded fragment response content could not be encoded, the original body was kept.',
                [
                    'fragment' => $fragmentName,
                    'exception' => $exception,
                ]
            );
        }
    }

    /**
     * Keeps the hex escaping of the layout JsonResponse, which makes the markup safe to inline in a script.
     *
     * @param array<string, mixed> $content
     */
    private function replaceBody(Response $response, array $content): void
    {
        if ($response instanceof JsonResponse) {
            $response->setData($content);

            return;
        }

        $response->setContent(
            json_encode($content, JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_THROW_ON_ERROR)
        );
    }
}
