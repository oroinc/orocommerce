<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\OroStorefrontEmbeddedExtension;
use Oro\Bundle\StorefrontEmbeddedBundle\OroStorefrontEmbeddedBundle;
use Oro\Component\Config\CumulativeResourceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OroStorefrontEmbeddedExtensionTest extends TestCase
{
    private ContainerBuilder $container;

    #[\Override]
    protected function setUp(): void
    {
        $bundle = new OroStorefrontEmbeddedBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)]);

        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'prod');

        $extension = new OroStorefrontEmbeddedExtension();
        $extension->load([], $this->container);
    }

    #[\Override]
    protected function tearDown(): void
    {
        CumulativeResourceManager::getInstance()->clear();
    }

    public function testSetsFragmentsParameterFromStorefrontEmbeddedConfig(): void
    {
        $fragments = $this->container->getParameter('oro_storefront_embedded.fragments');

        self::assertSame(
            [
                'route_name' => 'oro_customer_frontend_customer_user_profile',
                'layout_block_ids' => ['embedded_profile'],
            ],
            $fragments['embedded_profile']
        );
    }

    /**
     * @dataProvider registersFragmentListenerWithEventMethodAndPriorityProvider
     *
     * @param array<int, array{event: string, method: string, priority: int}> $expectedTags
     */
    public function testRegistersFragmentListenerWithEventMethodAndPriority(
        string $serviceId,
        array $expectedTags
    ): void {
        self::assertSame(
            $expectedTags,
            $this->container->getDefinition($serviceId)->getTag('kernel.event_listener')
        );
    }

    /**
     * @return iterable<string, array{string, array<int, array{event: string, method: string, priority: int}>}>
     */
    public static function registersFragmentListenerWithEventMethodAndPriorityProvider(): iterable
    {
        yield 'request listener runs between the redirect firewall and the router listener' => [
            'oro_storefront_embedded.event_listener.embedded_fragment_request',
            [['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 33]],
        ];
        yield 'response listener runs before the listeners that rewrite the response' => [
            'oro_storefront_embedded.event_listener.embedded_fragment_response',
            [['event' => 'kernel.response', 'method' => 'onKernelResponse', 'priority' => 10]],
        ];
    }
}
