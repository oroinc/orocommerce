<?php

namespace Oro\Bundle\SaleBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
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
 */
class QuoteExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_sale_quote';
    const FRONTEND_SYSTEM_CONFIG_PATH = 'oro_rfp.frontend_product_visibility';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return QuoteProductFormatter
     */
    protected function getQuoteProductFormatter()
    {
        return $this->container->get('oro_sale.formatter.quote_product');
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get('oro_config.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_format_sale_quote_product_offer',
                [$this, 'formatProductOffer']
            ),
            new TwigFilter(
                'oro_format_sale_quote_product_type',
                [$this, 'formatProductType']
            ),
            new TwigFilter(
                'oro_format_sale_quote_product_request',
                [$this, 'formatProductRequest']
            ),
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_quote_visible', [$this, 'isQuoteVisible'])
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_sale.formatter.quote_product' => QuoteProductFormatter::class,
            'oro_config.manager' => ConfigManager::class,
        ];
    }
}
