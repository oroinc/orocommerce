<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Request\LineItem\ApruveLineItemRequestData;
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
        $product = $this->lineItem->getProduct();
        $this->data += [
            self::PRICE_TOTAL_CENTS => (int)$this->normalizePrice($this->lineItem->getPrice()),
            self::QUANTITY => (int)$this->lineItem->getQuantity(),
            self::CURRENCY => (string)$this->lineItem->getPrice()->getCurrency(),
            self::SKU => (string)$this->lineItem->getProductSku(),
            self::TITLE => (string)$product->getName(),
            self::DESCRIPTION => (string)$this->getDescription($product),
            self::VIEW_PRODUCT_URL => (string)$this->getViewProductUrl($product),
        ];

        return new ApruveLineItemRequestData($this->data);
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
     * {@inheritDoc}
     */
    public function setAmountEa($amount)
    {
        $this->data[self::PRICE_EA_CENTS] = $this->normalizeAmount($amount);

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
        $description = (string) $product->getDescription();
        $description = strip_tags($description);

        return str_replace(PHP_EOL, ' ', $description);
    }
}
