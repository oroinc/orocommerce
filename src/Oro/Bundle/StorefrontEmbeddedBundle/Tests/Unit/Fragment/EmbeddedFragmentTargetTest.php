<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTarget;
use PHPUnit\Framework\TestCase;

final class EmbeddedFragmentTargetTest extends TestCase
{
    public function testConstructorSetsAllReadonlyProperties(): void
    {
        $target = new EmbeddedFragmentTarget(
            'embedded_profile',
            'oro_customer_frontend_customer_user_profile',
            'https://example.test/customer/profile?tab=orders',
            '/customer/profile',
            ['_route' => 'oro_customer_frontend_customer_user_profile', 'id' => 42],
            ['tab' => 'orders']
        );

        self::assertSame('embedded_profile', $target->block);
        self::assertSame('oro_customer_frontend_customer_user_profile', $target->routeName);
        self::assertSame('https://example.test/customer/profile?tab=orders', $target->url);
        self::assertSame('/customer/profile', $target->path);
        self::assertSame(
            ['_route' => 'oro_customer_frontend_customer_user_profile', 'id' => 42],
            $target->attributes
        );
        self::assertSame(['tab' => 'orders'], $target->routeParameters);
    }
}
