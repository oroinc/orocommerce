<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\GuestAccess;

use Oro\Bundle\StorefrontEmbeddedBundle\GuestAccess\EmbeddedGuestAccessAllowedUrlsProvider;
use PHPUnit\Framework\TestCase;

final class EmbeddedGuestAccessAllowedUrlsProviderTest extends TestCase
{
    public function testGetAllowedUrlsPatternsReturnsExactlyTheConstructorArgument(): void
    {
        $patterns = ['^/embedded/theme-assets$'];

        $provider = new EmbeddedGuestAccessAllowedUrlsProvider($patterns);

        self::assertSame($patterns, $provider->getAllowedUrlsPatterns());
    }
}
