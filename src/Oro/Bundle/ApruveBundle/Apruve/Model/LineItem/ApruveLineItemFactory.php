<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntityFactory;
use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemFactory extends AbstractApruveEntityFactory implements ApruveLineItemFactoryInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param SupportedCurrenciesProviderInterface $supportedCurrenciesProvider
     * @param RouterInterface $router
     */
    public function __construct(
        SupportedCurrenciesProviderInterface $supportedCurrenciesProvider,
        RouterInterface $router
    ) {
        parent::__construct($supportedCurrenciesProvider);

        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    public function createFromOrderLineItem(OrderLineItem $lineItem)
    {
        $data = [
            ApruveLineItem::PRICE_TOTAL_CENTS => (int) $this->getPriceTotalCents($lineItem),
            ApruveLineItem::PRICE_EA_CENTS => (int) $this->getPriceEaCents($lineItem),
            ApruveLineItem::QUANTITY => (int) $lineItem->getQuantity(),
            ApruveLineItem::CURRENCY => (string) $this->getCurrency($lineItem->getCurrency()),
            ApruveLineItem::SKU => (string) $lineItem->getProductSku(),
            ApruveLineItem::TITLE => (string) $lineItem->getParentProduct()->getName(),
            ApruveLineItem::DESCRIPTION => (string) $lineItem->getParentProduct()->getDescription(),
            ApruveLineItem::VIEW_PRODUCT_URL => (string) $this->getViewProductUrl($lineItem->getParentProduct()),
        ];

        return new ApruveLineItem($data);
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return int
     */
    protected function getPriceEaCents(OrderLineItem $lineItem)
    {
        $amount = $lineItem->getValue();
        if ($lineItem->getPriceType() === PriceTypeAwareInterface::PRICE_TYPE_BUNDLED) {
            $amount /= $lineItem->getQuantity();
        }

        return $this->normalizeAmount($amount);
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return int
     */
    protected function getPriceTotalCents(OrderLineItem $lineItem)
    {
        $amount = $lineItem->getValue();
        if ($lineItem->getPriceType() === PriceTypeAwareInterface::PRICE_TYPE_UNIT) {
            $amount *= $lineItem->getQuantity();
        }

        return $this->normalizeAmount($amount);
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    protected function getViewProductUrl(Product $product)
    {
        return $this->router->generate('oro_product_view', ['id' => $product->getId()]);
    }
}
