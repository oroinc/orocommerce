<?php

namespace Oro\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\OrderBundle\Entity\OrderHolderInterface;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;

/**
 * Set tax_code and digital flag values for orderLineItem context during tax calculation.
 */
class OrderLineItemHandler
{
    /**
     * @var string[]
     */
    protected array $taxCodes = [];

    public function __construct(
        private TaxationAddressProvider $addressProvider,
        private TaxCodeProvider $taxCodeProvider,
    ) {
    }

    public function onContextEvent(ContextEvent $contextEvent): void
    {
        $lineItem = $contextEvent->getMappingObject();
        if (!$lineItem instanceof OrderHolderInterface) {
            return;
        }

        $context = $contextEvent->getContext();

        $context->offsetSet(Taxable::DIGITAL_PRODUCT, $this->isDigital($lineItem));
        $context->offsetSet(Taxable::PRODUCT_TAX_CODE, $this->getProductTaxCode($lineItem));
        $context->offsetSet(Taxable::ACCOUNT_TAX_CODE, $this->getCustomerTaxCode($lineItem));
    }

    protected function isDigital(OrderHolderInterface $lineItem): bool
    {
        $productTaxCode = $this->getProductTaxCode($lineItem);

        if (null === $productTaxCode) {
            return false;
        }

        $billingAddress = $lineItem->getOrder()->getBillingAddress();
        $shippingAddress = $lineItem->getOrder()->getShippingAddress();

        $address = $this->addressProvider->getTaxationAddress($billingAddress, $shippingAddress);

        if (null === $address) {
            return false;
        }

        return $this->addressProvider->isDigitalProductTaxCode($address->getCountryIso2(), $productTaxCode);
    }

    protected function getProductTaxCode(OrderHolderInterface $lineItem): ?string
    {
        $cacheKey  = $this->getCacheTaxCodeKey(TaxCodeInterface::TYPE_PRODUCT, $lineItem);
        $cachedTaxCode = $this->getCachedTaxCode($cacheKey);

        if ($cachedTaxCode) {
            return $cachedTaxCode;
        }

        $product = $lineItem->getProduct();
        $this->taxCodes[$cacheKey] = null;

        if ($product) {
            $this->taxCodes[$cacheKey] = $this->getTaxCode(TaxCodeInterface::TYPE_PRODUCT, $product);
        }

        return $this->taxCodes[$cacheKey];
    }

    protected function getCustomerTaxCode(OrderHolderInterface $lineItem): ?string
    {
        $cacheKey  = $this->getCacheTaxCodeKey(TaxCodeInterface::TYPE_ACCOUNT, $lineItem);
        $cachedTaxCode = $this->getCachedTaxCode($cacheKey);

        if ($cachedTaxCode) {
            return $cachedTaxCode;
        }

        $taxCode = null;
        $customer = null;

        if ($lineItem->getOrder() && $lineItem->getOrder()->getCustomer()) {
            $customer = $lineItem->getOrder()->getCustomer();
            $taxCode = $this->getTaxCode(TaxCodeInterface::TYPE_ACCOUNT, $customer);
        }

        if (!$taxCode && $customer && $customer->getGroup()) {
            $taxCode = $this->getTaxCode(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $customer->getGroup());
        }

        $this->taxCodes[$cacheKey] = $taxCode;

        return $taxCode;
    }

    protected function getTaxCode(string $type, object $object): ?string
    {
        return $this->taxCodeProvider->getTaxCode($type, $object)?->getCode();
    }

    protected function getCacheTaxCodeKey(string $type, OrderHolderInterface $orderLineItem): string
    {
        $id = $orderLineItem->getId() ?: spl_object_hash($orderLineItem);

        return implode(':', [$type, $id]);
    }

    protected function getCachedTaxCode(string $cacheKey): ?string
    {
        return $this->taxCodes[$cacheKey] ?? null;
    }
}
