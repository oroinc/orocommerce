<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Serves image urls to an embedded host as absolute Oro urls, so the browser loads them from Oro and the host
 * does not have to relay Oro's media cache. Same reference-type switch the storefront API makes for its own
 * consumers, driven by the embedded request instead of the API one.
 */
class EmbeddedRequestFileUrlProvider implements FileUrlProviderInterface
{
    public function __construct(
        private readonly FileUrlProviderInterface $innerFileUrlProvider,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    #[\Override]
    public function getFileUrl(
        File $file,
        string $action = self::FILE_ACTION_GET,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->innerFileUrlProvider->getFileUrl($file, $action, $referenceType);
    }

    #[\Override]
    public function getResizedImageUrl(
        File $file,
        int $width,
        int $height,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->innerFileUrlProvider->getResizedImageUrl(
            $file,
            $width,
            $height,
            $format,
            $this->embeddedReferenceType($referenceType)
        );
    }

    #[\Override]
    public function getFilteredImageUrl(
        File $file,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->innerFileUrlProvider->getFilteredImageUrl(
            $file,
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
