<?php

namespace Oro\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;

/**
 * Set tax_code value for order context during tax calculation.
 */
class OrderHandler
{
    /**
     * @var string[]
     */
    protected array $taxCodes = [];

    public function __construct(
        private TaxCodeProvider $taxCodeProvider
    ) {
    }

    public function onContextEvent(ContextEvent $contextEvent): void
    {
        $order = $contextEvent->getMappingObject();
        if (!$order instanceof Order) {
            return;
        }

        $context = $contextEvent->getContext();
        $products = $order->getProductsFromLineItems();

        $this->taxCodeProvider->preloadTaxCodes(TaxCodeInterface::TYPE_PRODUCT, $products);

        $context->offsetSet(Taxable::ACCOUNT_TAX_CODE, $this->getCustomerTaxCode($order));
    }

    protected function getCustomerTaxCode(Order $order): ?string
    {
        $cacheKey  = $this->getCacheTaxCodeKey(TaxCodeInterface::TYPE_ACCOUNT, $order);
        $cachedTaxCode = $this->getCachedTaxCode($cacheKey);

        if ($cachedTaxCode) {
            return $cachedTaxCode;
        }

        $taxCode = null;

        if ($order->getCustomer()) {
            $taxCode = $this->getTaxCode(TaxCodeInterface::TYPE_ACCOUNT, $order->getCustomer());
        }

        if (!$taxCode && $order->getCustomer() && $order->getCustomer()->getGroup()) {
            $taxCode = $this->getTaxCode(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $order->getCustomer()->getGroup());
        }

        $this->taxCodes[$cacheKey] = $taxCode;

        return $taxCode;
    }

    protected function getTaxCode(string $type, object $object): ?string
    {
        return $this->taxCodeProvider->getTaxCode($type, $object)?->getCode();
    }

    protected function getCacheTaxCodeKey(string $type, Order $order): string
    {
        $id = $order->getId() ?: spl_object_hash($order);

        return implode(':', [$type, $id]);
    }

    protected function getCachedTaxCode(string $cacheKey): ?string
    {
        return $this->taxCodes[$cacheKey] ?? null;
    }
}
