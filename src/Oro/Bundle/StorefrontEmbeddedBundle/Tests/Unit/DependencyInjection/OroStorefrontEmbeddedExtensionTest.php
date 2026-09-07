<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedSessionController;
use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\OroStorefrontEmbeddedExtension;
use Oro\Bundle\StorefrontEmbeddedBundle\Extension\EmbeddedRequestCurrentLocalizationExtension;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\OroStorefrontEmbeddedBundle;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRequestCurrentCurrencyProvider;
use Oro\Component\Config\CumulativeResourceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroStorefrontEmbeddedExtensionTest extends TestCase
{
    private const string LAYOUTS_DIR = 'StorefrontEmbeddedBundle/Resources/views/layouts';

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

    public function testSetsBlocksParameterToEveryBlockDeclaredByLayoutUpdates(): void
    {
        self::assertEquals(
            [
                'embedded_shopping_lists' => 'oro_shopping_list_frontend_index',
                'embedded_shopping_list' => 'oro_shopping_list_frontend_update',
                'embedded_shopping_list_view' => 'oro_shopping_list_frontend_view',
                'embedded_profile' => 'oro_customer_frontend_customer_user_profile',
                'embedded_profile_update' => 'oro_customer_frontend_customer_user_profile_update',
                'embedded_profile_update_email' => 'oro_customer_frontend_customer_user_profile_update_email',
                'embedded_profile_update_password' => 'oro_customer_frontend_customer_user_profile_update_password',
                'embedded_customer_user_addresses' => 'oro_customer_frontend_customer_user_address_index',
                'embedded_customer_user_address_create' => 'oro_customer_frontend_customer_user_address_create',
                'embedded_customer_user_address_update' => 'oro_customer_frontend_customer_user_address_update',
                'embedded_customer_address_create' => 'oro_customer_frontend_customer_address_create',
                'embedded_customer_address_update' => 'oro_customer_frontend_customer_address_update',
                'embedded_customer_users' => 'oro_customer_frontend_customer_user_index',
                'embedded_customer_user_create' => 'oro_customer_frontend_customer_user_create',
                'embedded_customer_user_update' => 'oro_customer_frontend_customer_user_update',
                'embedded_customer_user_view' => 'oro_customer_frontend_customer_user_view',
                'embedded_customer_user_roles' => 'oro_customer_frontend_customer_user_role_index',
                'embedded_customer_user_role_create' => 'oro_customer_frontend_customer_user_role_create',
                'embedded_customer_user_role_update' => 'oro_customer_frontend_customer_user_role_update',
                'embedded_customer_user_role_view' => 'oro_customer_frontend_customer_user_role_view',
                'embedded_customer_user_dashboard' => 'oro_customer_frontend_customer_user_dashboard_index',
                'embedded_rfp_requests' => 'oro_rfp_frontend_request_index',
                'embedded_rfp_request_create' => 'oro_rfp_frontend_request_create',
                'embedded_rfp_request_view' => 'oro_rfp_frontend_request_view',
                'embedded_rfp_request_update' => 'oro_rfp_frontend_request_update',
                'embedded_rfp_request_success' => 'oro_rfp_frontend_request_success',
                'embedded_orders' => 'oro_order_frontend_index',
                'embedded_order_view' => 'oro_order_frontend_view',
                'embedded_quotes' => 'oro_sale_quote_frontend_index',
                'embedded_quote_view' => 'oro_sale_quote_frontend_view',
                'embedded_quote_choice' => 'oro_sale_quote_frontend_choice',
                'embedded_invoices' => 'oro_commerce_invoice_frontend_index',
                'embedded_invoice_view' => 'oro_commerce_invoice_frontend_view',
                'embedded_invoice_payment' => 'oro_invoice_payment_frontend_invoice_payment',
                'embedded_invoice_payment_success' => 'oro_invoice_payment_frontend_invoice_payment_success',
                'embedded_invoice_payment_error' => 'oro_invoice_payment_frontend_invoice_payment_error',
                'embedded_previously_purchased' => 'oro_order_products_frontend_previously_purchased',
                'embedded_recurring_orders' => 'oro_recurring_order_storefront_recurring_order_index',
                'embedded_recurring_order_view' => 'oro_recurring_order_storefront_recurring_order_view',
                'embedded_recurring_order_create_from_order' =>
                    'oro_recurring_order_storefront_recurring_order_create_from_order',
                'embedded_recurring_order_create_from_recurring_order' =>
                    'oro_recurring_order_storefront_recurring_order_create_from_recurring_order'
            ],
            $this->container->getParameter('oro_storefront_embedded.blocks')
        );
    }

    public function testSetsProxiedRoutesParameterFromStorefrontEmbeddedConfig(): void
    {
        self::assertEquals(
            [
                'embedded_shopping_list' => ['oro_pricing_frontend_price_by_customer'],
                'embedded_shopping_list_view' => ['oro_pricing_frontend_price_by_customer']
            ],
            $this->container->getParameter('oro_storefront_embedded.proxied_routes')
        );
    }

    public function testSetsFileRoutesParameterFromStorefrontEmbeddedConfig(): void
    {
        self::assertEquals(
            [
                'any_block' => [
                    'oro_frontend_attachment_get_file',
                    'oro_frontend_pdf_generator_pdf_document_file'
                ],
                'embedded_order_view' => ['oro_order_frontend_pdf_document_download']
            ],
            $this->container->getParameter('oro_storefront_embedded.file_routes')
        );
    }

    /**
     * @dataProvider taggedServiceDataProvider
     */
    public function testRegistersServiceWithTagThatPlugsItIntoItsExtensionPoint(
        string $serviceId,
        string $tagName
    ): void {
        self::assertTrue($this->container->getDefinition($serviceId)->hasTag($tagName));
    }

    public function taggedServiceDataProvider(): array
    {
        return [
            'layout context configurator' => [
                EmbeddedContextConfigurator::class,
                'layout.context_configurator'
            ],
            'current localization extension' => [
                EmbeddedRequestCurrentLocalizationExtension::class,
                'oro_locale.extension.current_localization'
            ],
            'current currency provider' => [
                EmbeddedRequestCurrentCurrencyProvider::class,
                'oro_pricing.current_currency_provider'
            ],
            'fragment controller' => [
                EmbeddedFragmentController::class,
                'container.service_subscriber'
            ],
            'session controller' => [
                EmbeddedSessionController::class,
                'controller.service_arguments'
            ]
        ];
    }

    public function testPassesDiscoveredBlocksToLayoutContextConfigurator(): void
    {
        self::assertSame(
            '%oro_storefront_embedded.blocks%',
            $this->container->getDefinition(EmbeddedContextConfigurator::class)->getArgument(1)
        );
    }

    public function testTracksEmbeddedBlocksLayoutUpdatesAsContainerResourcesToRebuildTheBlocksParameter(): void
    {
        $container = self::containerRecordingResourcesSymfonyDropsInsideVendor();
        $container->setParameter('kernel.environment', 'prod');

        (new OroStorefrontEmbeddedExtension())->load([], $container);

        $registeredLayoutPatterns = [];
        $registeredFiles = [];
        foreach ($container->addedResources as $resource) {
            if ($resource instanceof DirectoryResource && str_ends_with($resource->getResource(), self::LAYOUTS_DIR)) {
                $registeredLayoutPatterns[$resource->getResource()] = $resource->getPattern();
            } elseif ($resource instanceof FileResource) {
                $registeredFiles[$resource->getResource()] = true;
            }
        }

        self::assertCount(1, $registeredLayoutPatterns);

        $layoutsDir = array_key_first($registeredLayoutPatterns);
        self::assertSame('/embedded_blocks.yml$/', $registeredLayoutPatterns[$layoutsDir]);

        $embeddedBlocksFiles = glob($layoutsDir . '/*/*/embedded_blocks.yml');
        self::assertNotEmpty($embeddedBlocksFiles);
        foreach ($embeddedBlocksFiles as $embeddedBlocksFile) {
            self::assertArrayHasKey($embeddedBlocksFile, $registeredFiles);
        }
    }

    private static function containerRecordingResourcesSymfonyDropsInsideVendor(): ContainerBuilder
    {
        return new class () extends ContainerBuilder {
            /** @var ResourceInterface[] */
            public array $addedResources = [];

            #[\Override]
            public function addResource(ResourceInterface $resource): static
            {
                $this->addedResources[] = $resource;

                return parent::addResource($resource);
            }
        };
    }
}
