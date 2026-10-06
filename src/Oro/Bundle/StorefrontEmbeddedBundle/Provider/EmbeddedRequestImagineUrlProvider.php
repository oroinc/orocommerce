<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Serves filtered URLs of images shipped with the theme as absolute URLs.
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
            $this->resolveReferenceType($referenceType)
        );
    }

    private function resolveReferenceType(int $referenceType): int
    {
        if (
            $referenceType !== UrlGeneratorInterface::ABSOLUTE_PATH ||
            !$this->embeddedRequestChecker->isEmbeddedRequest()
        ) {
            return $referenceType;
        }

        return UrlGeneratorInterface::ABSOLUTE_URL;
    }
}
