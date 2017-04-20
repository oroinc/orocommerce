<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Builder\AbstractApruveEntityBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemBuilder extends AbstractApruveEntityBuilder implements ApruveLineItemBuilderInterface
{
    /**
     * Mandatory
     */
    const PRICE_TOTAL_CENTS = 'price_total_cents';
    /**
     * Property 'price_total_cents' is not respected by Apruve when secure hash is generated,
     * hence use 'amount_cents' instead.
     */
    const AMOUNT_CENTS = 'amount_cents';
    const QUANTITY = 'quantity';
    const CURRENCY = 'currency';
    const SKU = 'sku';

    /**
     * Optional
     */
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const VIEW_PRODUCT_URL = 'view_product_url';
    const PRICE_EA_CENTS = 'price_ea_cents';
    const VENDOR = 'vendor';
    const MERCHANT_NOTES = 'merchant_notes';
    const VARIANT_INFO = 'variant_info';

    /**
     * @var PaymentLineItemInterface
     */
    private $lineItem;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param PaymentLineItemInterface $lineItem
     * @param RouterInterface $router
     */
    public function __construct(PaymentLineItemInterface $lineItem, RouterInterface $router)
    {
        $this->lineItem = $lineItem;
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $this->data += [
            self::TITLE => (string)$this->getTitle($this->lineItem),
            self::SKU => (string)$this->getSku($this->lineItem),
            self::AMOUNT_CENTS => (int)$this->normalizePrice($this->lineItem->getPrice()),
            self::PRICE_EA_CENTS => (int)$this->getPriceEaCents($this->lineItem),
            self::QUANTITY => (int)$this->lineItem->getQuantity(),
            self::CURRENCY => (string)$this->lineItem->getPrice()->getCurrency(),
        ];

        $product = $this->lineItem->getProduct();
        if ($product instanceof Product) {
            $this->data += [
                self::DESCRIPTION => (string)$this->getDescription($product),
                self::VIEW_PRODUCT_URL => (string)$this->getViewProductUrl($product),
            ];
        }

        return new ApruveLineItem($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantNotes($notes)
    {
        $this->data[self::MERCHANT_NOTES] = (string)$notes;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVendor($vendor)
    {
        $this->data[self::VENDOR] = (string)$vendor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVariantInfo($info)
    {
        $this->data[self::VARIANT_INFO] = (string)$info;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->data[self::TITLE] = (string)$title;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->data[self::DESCRIPTION] = $this->sanitizeDescription($description);

        return $this;
    }

    /**
     * @param string $url
     *
     * @return self
     */
    public function setViewProductUrl($url)
    {
        $this->data[self::VIEW_PRODUCT_URL] = (string)$url;

        return $this;
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    protected function getViewProductUrl(Product $product)
    {
        return $this->router->generate(
            'oro_product_frontend_product_view',
            ['id' => $product->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    protected function getDescription(Product $product)
    {
        $description = (string)$product->getDescription();

        return $this->sanitizeDescription($description);
    }

    /**
     * @param PaymentLineItemInterface $lineItem
     *
     * @return int
     */
    protected function getPriceEaCents(PaymentLineItemInterface $lineItem)
    {
        $amount = (float)$lineItem->getPrice()->getValue();
        $quantity = $lineItem->getQuantity();

        return $this->normalizeAmount($amount / $quantity);
    }

    /**
     * @param PaymentLineItemInterface $lineItem
     *
     * @return string
     */
    protected function getSku(PaymentLineItemInterface $lineItem)
    {
        $sku = $lineItem->getProductSku();

        // Product sku is optional, and will be null is not provided to builder.
        if ($sku === null) {
            // Try to fetch it directly from product.
            $product = $lineItem->getProduct();
            // ... though it is optional as well.
            if ($product !== null) {
                $sku = $product->getSku();
            }
        }

        return (string) $sku;
    }

    /**
     * @param PaymentLineItemInterface $lineItem
     *
     * @return string
     */
    protected function getTitle(PaymentLineItemInterface $lineItem)
    {
        $product = $lineItem->getProduct();
        // Product is optional PaymentLineItemBuilderInterface.
        if ($product !== null) {
            $title = $product->getName();
        } else {
            // ... though title is required by Apruve, so use SKU when no product is available.
            $title = $this->getSku($lineItem);
        }

        return (string) $title;
    }

    /**
     * @param string $description
     *
     * @return string
     */
    protected function sanitizeDescription($description)
    {
        $description = strip_tags($description);

        return str_replace(PHP_EOL, ' ', $description);
    }
}
