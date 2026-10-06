<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Decorates file URL provider to make it return absolute URLs for filtered and resized images rendered within
 * an embedded fragment.
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
            $this->resolveReferenceType($referenceType)
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
