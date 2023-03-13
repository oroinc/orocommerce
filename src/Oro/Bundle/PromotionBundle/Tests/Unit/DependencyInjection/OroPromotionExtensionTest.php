<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPromotionExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroPromotionExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'feature_enabled' => ['value' => true, 'scope' => 'app'],
                        'discount_strategy' => ['value' => 'apply_all', 'scope' => 'app'],
                        'case_insensitive_coupon_search' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_promotion')
        );
    }
}
