<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\StorefrontEmbeddedBundle\Security\StorefrontAccessToken;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

final class StorefrontAccessTokenTest extends TestCase
{
    public function testConstructorSetsUserIdentifierAndOrganization(): void
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 7);

        $token = new StorefrontAccessToken('customer@example.test', $organization);

        self::assertSame('customer@example.test', $token->userIdentifier);
        self::assertSame($organization, $token->organization);
    }
}
