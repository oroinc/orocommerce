<?php

namespace Oro\Bundle\SaleBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides a Twig function to check visibility of a sales quote:
 *   - is_quote_visible
 *
 * Provides Twig filters to format sales quote data:
 *   - oro_format_sale_quote_product_offer
 *   - oro_format_sale_quote_product_type
 *   - oro_format_sale_quote_product_request
 *
 * Provides a Twig function to create a storefront link for accessing a quote by using its guest access identifier:
 *   - quote_guest_access_link
 */
class QuoteExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const FRONTEND_SYSTEM_CONFIG_PATH = 'oro_rfp.frontend_product_visibility';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format_sale_quote_product_offer', [$this, 'formatProductOffer']),
            new TwigFilter('oro_format_sale_quote_product_type', [$this, 'formatProductType']),
            new TwigFilter('oro_format_sale_quote_product_request', [$this, 'formatProductRequest']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_quote_visible', [$this, 'isQuoteVisible']),
            new TwigFunction('quote_guest_access_link', [$this, 'getGuestAccessLink']),
        ];
    }

    /**
     * @param int $type
     * @return string
     */
    public function formatProductType($type)
    {
        return $this->getQuoteProductFormatter()->formatType($type);
    }

    /**
     * @param QuoteProductOffer $item
     * @return string
     */
    public function formatProductOffer(QuoteProductOffer $item)
    {
        return $this->getQuoteProductFormatter()->formatOffer($item);
    }

    /**
     * @param QuoteProductRequest $item
     * @return string
     */
    public function formatProductRequest(QuoteProductRequest $item)
    {
        return $this->getQuoteProductFormatter()->formatRequest($item);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isQuoteVisible(Product $product)
    {
        $supportedStatuses = (array)$this->getConfigManager()->get(self::FRONTEND_SYSTEM_CONFIG_PATH);
        $inventoryStatus = $product->getInventoryStatus();

        return $inventoryStatus && in_array($inventoryStatus->getId(), $supportedStatuses);
    }

    public function getGuestAccessLink(Quote $quote): ?string
    {
        if (!$quote->getWebsite() || !$this->getFeatureChecker()->isFeatureEnabled('guest_quote')) {
            return null;
        }

        return $this->getWebsiteUrlResolver()
            ->getWebsitePath(
                'oro_sale_quote_frontend_view_guest',
                ['guest_access_id' => $quote->getGuestAccessId()],
                $quote->getWebsite()
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_sale.formatter.quote_product' => QuoteProductFormatter::class,
            'oro_config.manager' => ConfigManager::class,
            FeatureChecker::class,
            WebsiteUrlResolver::class,
        ];
    }

    private function getQuoteProductFormatter(): QuoteProductFormatter
    {
        return $this->container->get('oro_sale.formatter.quote_product');
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.manager');
    }

    private function getFeatureChecker(): FeatureChecker
    {
        return $this->container->get(FeatureChecker::class);
    }

    private function getWebsiteUrlResolver(): WebsiteUrlResolver
    {
        return $this->container->get(WebsiteUrlResolver::class);
    }
}
