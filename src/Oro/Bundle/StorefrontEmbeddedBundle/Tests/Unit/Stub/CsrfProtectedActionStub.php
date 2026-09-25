<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;

/**
 * A minimal controller-shaped fixture carrying a #[CsrfProtection] attribute, so
 * EmbeddedFragmentAccessCheckerTest can exercise the CSRF check against a real, reflectable method.
 */
class CsrfProtectedActionStub
{
    #[CsrfProtection]
    public function saveAction(): void
    {
    }

    public function unprotectedAction(): void
    {
    }
}
