<?php

namespace Oro\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;

class OrderHandler
{
    /**
     * @var TaxCodeProvider
     */
    private $taxCodeProvider;

    /**
     * @var TaxCodeInterface[]
     */
    protected $taxCodes = [];

    public function __construct(TaxCodeProvider $taxCodeProvider)
    {
        $this->taxCodeProvider = $taxCodeProvider;
    }

    public function onContextEvent(ContextEvent $contextEvent)
    {
        $order = $contextEvent->getMappingObject();
        $context = $contextEvent->getContext();

        if (!$order instanceof Order) {
            return;
        }

        $products = $order->getProductsFromLineItems();

        $this->taxCodeProvider->preloadTaxCodes(TaxCodeInterface::TYPE_PRODUCT, $products);

        $context->offsetSet(Taxable::ACCOUNT_TAX_CODE, $this->getCustomerTaxCode($order));
    }

    /**
     * @param Order $order
     * @return null|string
     */
    protected function getCustomerTaxCode(Order $order)
    {
        $cacheKey  = $this->getCacheTaxCodeKey(TaxCodeInterface::TYPE_ACCOUNT, $order);
        $cachedTaxCode = $this->getCachedTaxCode($cacheKey);

        if ($cachedTaxCode !== false) {
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

    /**
     * @param string $type
     * @param object $object
     * @return string|null
     */
    protected function getTaxCode($type, $object)
    {
        $taxCode = $this->taxCodeProvider->getTaxCode($type, $object);

        return $taxCode ? $taxCode->getCode() : null;
    }

    /**
     * @param string $type
     * @param Order $order
     * @return string
     */
    protected function getCacheTaxCodeKey($type, Order $order)
    {
        $id = $order->getId() ?: spl_object_hash($order);

        return implode(':', [$type, $id]);
    }

    /**
     * @param string $cacheKey
     * @return false|TaxCodeInterface
     */
    protected function getCachedTaxCode($cacheKey)
    {
        if (!array_key_exists($cacheKey, $this->taxCodes)) {
            return false;
        }

        return $this->taxCodes[$cacheKey];
    }
}
