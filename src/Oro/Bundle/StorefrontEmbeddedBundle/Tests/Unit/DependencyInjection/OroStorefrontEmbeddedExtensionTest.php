<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedPreferencesController;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedSessionController;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedThemeAssetsController;
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

    public function testSetsBlocksParameterToEveryBlockDeclaredByStorefrontEmbeddedConfig(): void
    {
        self::assertSame(
            [
                'embedded_invoices' => 'oro_commerce_invoice_frontend_index',
                'embedded_invoice_view' => 'oro_commerce_invoice_frontend_view',
                'embedded_customer_address_create' => 'oro_customer_frontend_customer_address_create',
                'embedded_customer_address_update' => 'oro_customer_frontend_customer_address_update',
                'embedded_customer_user_address_create' => 'oro_customer_frontend_customer_user_address_create',
                'embedded_customer_user_addresses' => 'oro_customer_frontend_customer_user_address_index',
                'embedded_customer_user_address_update' => 'oro_customer_frontend_customer_user_address_update',
                'embedded_customer_user_create' => 'oro_customer_frontend_customer_user_create',
                'embedded_customer_user_dashboard' => 'oro_customer_frontend_customer_user_dashboard_index',
                'embedded_customer_users' => 'oro_customer_frontend_customer_user_index',
                'embedded_profile' => 'oro_customer_frontend_customer_user_profile',
                'embedded_profile_update' => 'oro_customer_frontend_customer_user_profile_update',
                'embedded_profile_update_email' => 'oro_customer_frontend_customer_user_profile_update_email',
                'embedded_profile_update_password' => 'oro_customer_frontend_customer_user_profile_update_password',
                'embedded_customer_user_role_create' => 'oro_customer_frontend_customer_user_role_create',
                'embedded_customer_user_roles' => 'oro_customer_frontend_customer_user_role_index',
                'embedded_customer_user_role_update' => 'oro_customer_frontend_customer_user_role_update',
                'embedded_customer_user_role_view' => 'oro_customer_frontend_customer_user_role_view',
                'embedded_customer_user_update' => 'oro_customer_frontend_customer_user_update',
                'embedded_customer_user_view' => 'oro_customer_frontend_customer_user_view',
                'embedded_invoice_payment' => 'oro_invoice_payment_frontend_invoice_payment',
                'embedded_invoice_payment_error' => 'oro_invoice_payment_frontend_invoice_payment_error',
                'embedded_invoice_payment_success' => 'oro_invoice_payment_frontend_invoice_payment_success',
                'embedded_orders' => 'oro_order_frontend_index',
                'embedded_order_view' => 'oro_order_frontend_view',
                'embedded_previously_purchased' => 'oro_order_products_frontend_previously_purchased',
                'embedded_recurring_order_create_from_order' =>
                    'oro_recurring_order_storefront_recurring_order_create_from_order',
                'embedded_recurring_order_create_from_recurring_order' =>
                    'oro_recurring_order_storefront_recurring_order_create_from_recurring_order',
                'embedded_recurring_orders' => 'oro_recurring_order_storefront_recurring_order_index',
                'embedded_recurring_order_view' => 'oro_recurring_order_storefront_recurring_order_view',
                'embedded_rfp_request_create' => 'oro_rfp_frontend_request_create',
                'embedded_rfp_requests' => 'oro_rfp_frontend_request_index',
                'embedded_rfp_request_success' => 'oro_rfp_frontend_request_success',
                'embedded_rfp_request_update' => 'oro_rfp_frontend_request_update',
                'embedded_rfp_request_view' => 'oro_rfp_frontend_request_view',
                'embedded_quote_choice' => 'oro_sale_quote_frontend_choice',
                'embedded_quotes' => 'oro_sale_quote_frontend_index',
                'embedded_quote_view' => 'oro_sale_quote_frontend_view',
                'embedded_shopping_lists' => 'oro_shopping_list_frontend_index',
                'embedded_shopping_list' => 'oro_shopping_list_frontend_update',
                'embedded_shopping_list_view' => 'oro_shopping_list_frontend_view',
            ],
            $this->container->getParameter('oro_storefront_embedded.blocks')
        );
    }

    /**
     * @dataProvider registersServiceWithTagThatPlugsItIntoItsExtensionPointProvider
     */
    public function testRegistersServiceWithTagThatPlugsItIntoItsExtensionPoint(
        string $serviceId,
        string $tagName
    ): void {
        self::assertTrue($this->container->getDefinition($serviceId)->hasTag($tagName));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function registersServiceWithTagThatPlugsItIntoItsExtensionPointProvider(): iterable
    {
        yield 'layout context configurator' => [
            'oro_storefront_embedded.layout.extension.embedded_context_configurator',
            'layout.context_configurator',
        ];
        yield 'vary listener' => [
            'oro_storefront_embedded.event_listener.embedded_request_vary',
            'kernel.event_listener',
        ];
        yield 'guest access allowed urls provider' => [
            'oro_storefront_embedded.guest_access.embedded_guest_access_allowed_urls_provider',
            'oro_frontend.guest_access_urls_provider',
        ];
        yield 'fragment controller' => [
            EmbeddedFragmentController::class,
            'container.service_subscriber',
        ];
        yield 'preferences controller' => [
            EmbeddedPreferencesController::class,
            'container.service_subscriber',
        ];
        yield 'session controller' => [
            EmbeddedSessionController::class,
            'container.service_subscriber',
        ];
        yield 'theme assets controller' => [
            EmbeddedThemeAssetsController::class,
            'container.service_subscriber',
        ];
    }

    /**
     * @dataProvider registersFragmentListenerWithItsEventMethodAndPriorityProvider
     *
     * @param array<int, array{event: string, method: string, priority: int}> $expectedTags
     */
    public function testRegistersFragmentListenerWithItsEventMethodAndPriority(
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
    public static function registersFragmentListenerWithItsEventMethodAndPriorityProvider(): iterable
    {
        yield 'feature toggle listener runs before the platform feature toggle request listener' => [
            'oro_storefront_embedded.event_listener.embedded_fragment_feature_toggle',
            [['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 1]],
        ];
        yield 'csrf protection listener runs after the platform controller listener' => [
            'oro_storefront_embedded.event_listener.embedded_fragment_csrf_protection',
            [['event' => 'kernel.controller', 'method' => 'onKernelController', 'priority' => -10]],
        ];
        yield 'access listener runs after the platform security controller listener' => [
            'oro_storefront_embedded.event_listener.embedded_fragment_access',
            [['event' => 'kernel.controller_arguments', 'method' => 'onKernelControllerArguments', 'priority' => -2]],
        ];
    }

    public function testPassesConfiguredBlocksToLayoutContextConfigurator(): void
    {
        self::assertSame(
            '%oro_storefront_embedded.blocks%',
            $this->container
                ->getDefinition('oro_storefront_embedded.layout.extension.embedded_context_configurator')
                ->getArgument(1)
        );
    }
}
