<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tools;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\UIBundle\Tools\UrlHelper;

/**
 * Turns the root-relative paths the storefront reads from its search index (product images above all) into
 * absolute Oro urls while rendering for an embedded host, which cannot serve Oro's paths from its own origin.
 *
 * Extends UrlHelper only to satisfy strict type-hints; every method is delegated.
 */
class EmbeddedRequestUrlHelper extends UrlHelper
{
    public function __construct(
        private readonly UrlHelper $innerUrlHelper,
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
