<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Serves filtered urls of images shipped with the theme — image placeholders above all — as absolute Oro urls
 * while rendering for an embedded host, the same way EmbeddedRequestFileUrlProvider serves attachments.
 */
class EmbeddedRequestImagineUrlProvider implements ImagineUrlProviderInterface
{
    public function __construct(
        private readonly ImagineUrlProviderInterface $innerImagineUrlProvider,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    #[\Override]
    public function getFilteredImageUrl(
        string $path,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string {
        return $this->innerImagineUrlProvider->getFilteredImageUrl(
            $path,
            $filterName,
            $format,
            $this->embeddedReferenceType($referenceType)
        );
    }

    private function embeddedReferenceType(int $referenceType): int
    {
        if ($referenceType !== UrlGeneratorInterface::ABSOLUTE_PATH) {
            return $referenceType;
        }

        return $this->embeddedRequestChecker->isEmbeddedRequest()
            ? UrlGeneratorInterface::ABSOLUTE_URL
            : $referenceType;
    }
}
