<?php

namespace OroB2B\Bundle\SaleBundle\Twig;

use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

class QuoteExtension extends \Twig_Extension
{
    const NAME = 'orob2b_sale_quote';

    /**
     * @var QuoteProductFormatter
     */
    protected $quoteProductFormatter;

    /**
     * @param QuoteProductFormatter $quoteProductFormatter
     */
    public function __construct(QuoteProductFormatter $quoteProductFormatter) {
        $this->quoteProductFormatter = $quoteProductFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_offer',
                [$this, 'formatProductOffer'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_type',
                [$this, 'formatProductType'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_request',
                [$this, 'formatProductRequest'],
                ['is_safe' => ['html']]
            ),
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
