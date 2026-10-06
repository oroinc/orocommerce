<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\GuestAccess;

use Oro\Bundle\StorefrontEmbeddedBundle\GuestAccess\EmbeddedFragmentAwareGuestAccessAllowedUrlsProvider;
use PHPUnit\Framework\TestCase;

final class EmbeddedFragmentAwareGuestAccessAllowedUrlsProviderTest extends TestCase
{
    public function testGetAllowedUrlsPatternsReturnsConstructorArgument(): void
    {
        $patterns = ['^/embedded/theme-assets$'];

        $provider = new EmbeddedFragmentAwareGuestAccessAllowedUrlsProvider($patterns);

        self::assertSame($patterns, $provider->getAllowedUrlsPatterns());
    }
}
