<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tools;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\UIBundle\Tools\UrlHelperInterface;

/**
 * Decorates URL helper to make it return absolute URLs for absolute paths rendered within an embedded fragment.
 */
class EmbeddedRequestUrlHelper implements UrlHelperInterface
{
    public function __construct(
        private readonly UrlHelperInterface $innerUrlHelper,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    #[\Override]
    public function getAbsolutePath(string $path): string
    {
        $absolutePath = $this->innerUrlHelper->getAbsolutePath($path);

        return $this->embeddedRequestChecker->isEmbeddedRequest()
            ? $this->innerUrlHelper->getAbsoluteUrl($absolutePath)
            : $absolutePath;
    }

    #[\Override]
    public function getAbsoluteUrl(string $path): string
    {
        return $this->innerUrlHelper->getAbsoluteUrl($path);
    }

    #[\Override]
    public function getRelativePath(string $path): string
    {
        return $this->innerUrlHelper->getRelativePath($path);
    }

    #[\Override]
    public function isLocalUrl(?string $url = null): bool
    {
        return $this->innerUrlHelper->isLocalUrl($url);
    }
}
