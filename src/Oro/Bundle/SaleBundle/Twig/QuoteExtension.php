<?php

namespace Oro\Bundle\SaleBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;

use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;

class QuoteExtension extends \Twig_Extension
{
    const NAME = 'oro_sale_quote';
    const FRONTEND_SYSTEM_CONFIG_PATH = 'oro_rfp.frontend_product_visibility';
    
    /**
     * @var QuoteProductFormatter
     */
    protected $quoteProductFormatter;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param QuoteProductFormatter $quoteProductFormatter
     * @param ConfigManager $configManager
     */
    public function __construct(QuoteProductFormatter $quoteProductFormatter, ConfigManager $configManager)
    {
        $this->quoteProductFormatter = $quoteProductFormatter;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_sale_quote_product_offer',
                [$this, 'formatProductOffer'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_sale_quote_product_type',
                [$this, 'formatProductType'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_sale_quote_product_request',
                [$this, 'formatProductRequest'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('is_quote_visible', [$this, 'isQuoteVisible'])
        ];
    }

    /**
     * @param int $type
     * @return string
     */
    public function formatProductType($type)
    {
        return $this->quoteProductFormatter->formatType($type);
    }

    /**
     * @param QuoteProductOffer $item
     * @return string
     */
    public function formatProductOffer(QuoteProductOffer $item)
    {
        return $this->quoteProductFormatter->formatOffer($item);
    }

    /**
     * @param QuoteProductRequest $item
     * @return string
     */
    public function formatProductRequest(QuoteProductRequest $item)
    {
        return $this->quoteProductFormatter->formatRequest($item);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isQuoteVisible(Product $product)
    {
        $supportedStatuses = (array)$this->configManager->get(self::FRONTEND_SYSTEM_CONFIG_PATH);
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
}
