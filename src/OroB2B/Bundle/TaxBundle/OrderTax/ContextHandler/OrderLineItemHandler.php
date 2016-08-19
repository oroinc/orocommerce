<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\ContextHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

class OrderLineItemHandler
{
    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $productTaxCodeClass;

    /**
     * @var string
     */
    protected $accountTaxCodeClass;

    /**
     * @var string
     */
    protected $orderLineItemClass;

    /**
     * @var TaxCodeInterface[]
     */
    protected $taxCodes = [];

    /**
     * @param TaxationAddressProvider $addressProvider
     * @param DoctrineHelper $doctrineHelper
     * @param string $productTaxCodeClass
     * @param string $accountTaxCodeClass
     * @param string $orderLineItemClass
     */
    public function __construct(
        TaxationAddressProvider $addressProvider,
        DoctrineHelper $doctrineHelper,
        $productTaxCodeClass,
        $accountTaxCodeClass,
        $orderLineItemClass
    ) {
        $this->addressProvider = $addressProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->productTaxCodeClass = $productTaxCodeClass;
        $this->accountTaxCodeClass = $accountTaxCodeClass;
        $this->orderLineItemClass = $orderLineItemClass;
    }

    /**
     * @param ContextEvent $contextEvent
     */
    public function onContextEvent(ContextEvent $contextEvent)
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $contextEvent->getMappingObject();
        $context = $contextEvent->getContext();

        if (!$lineItem instanceof $this->orderLineItemClass) {
            return;
        }

        $context->offsetSet(Taxable::DIGITAL_PRODUCT, $this->isDigital($lineItem));
        $context->offsetSet(Taxable::PRODUCT_TAX_CODE, $this->getProductTaxCode($lineItem));
        $context->offsetSet(Taxable::ACCOUNT_TAX_CODE, $this->getAccountTaxCode($lineItem));
    }

    /**
     * @param OrderLineItem $lineItem
     * @return bool
     */
    protected function isDigital(OrderLineItem $lineItem)
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

    /**
     * @param OrderLineItem $lineItem
     * @return null|TaxCodeInterface
     */
    protected function getProductTaxCode(OrderLineItem $lineItem)
    {
        $cacheKey  = $this->getCacheTaxCodeKey(TaxCodeInterface::TYPE_PRODUCT, $lineItem);
        $cachedTaxCode = $this->getCachedTaxCode($cacheKey);

        if ($cachedTaxCode !== false) {
            return $cachedTaxCode;
        }

        $product = $lineItem->getProduct();
        $this->taxCodes[$cacheKey] = null;

        if ($product) {
            $this->taxCodes[$cacheKey] = $this->getTaxCode(TaxCodeInterface::TYPE_PRODUCT, $product);
        }

        return $this->taxCodes[$cacheKey];
    }

    /**
     * @param OrderLineItem $lineItem
     * @return null|TaxCodeInterface
     */
    protected function getAccountTaxCode(OrderLineItem $lineItem)
    {
        $cacheKey  = $this->getCacheTaxCodeKey(TaxCodeInterface::TYPE_ACCOUNT, $lineItem);
        $cachedTaxCode = $this->getCachedTaxCode($cacheKey);

        if ($cachedTaxCode !== false) {
            return $cachedTaxCode;
        }

        $taxCode = null;
        $account = null;

        if ($lineItem->getOrder() && $lineItem->getOrder()->getAccount()) {
            $account = $lineItem->getOrder()->getAccount();
            $taxCode = $this->getTaxCode(TaxCodeInterface::TYPE_ACCOUNT, $account);
        }

        if (!$taxCode && $account && $account->getGroup()) {
            $taxCode = $this->getTaxCode(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $account->getGroup());
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
        $taxCode = $this->getRepository($type)->findOneByEntity((string)$type, $object);
        return $taxCode ? $taxCode->getCode() : null;
    }

    /**
     * @param string $type
     * @return AbstractTaxCodeRepository
     */
    protected function getRepository($type)
    {
        if ($type === TaxCodeInterface::TYPE_PRODUCT) {
            return $this->doctrineHelper->getEntityRepositoryForClass($this->productTaxCodeClass);
        } elseif ($type === TaxCodeInterface::TYPE_ACCOUNT || $type === TaxCodeInterface::TYPE_ACCOUNT_GROUP) {
            return $this->doctrineHelper->getEntityRepositoryForClass($this->accountTaxCodeClass);
        }

        throw new \InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }

    /**
     * @param string $type
     * @param OrderLineItem $orderLineItem
     * @return string
     */
    protected function getCacheTaxCodeKey($type, OrderLineItem $orderLineItem)
    {
        $id = $orderLineItem->getId() ?: spl_object_hash($orderLineItem);

        return implode(':', [$type, $id]);
    }

    /**
     * @param string $cacheKey
     * @return null|TaxCodeInterface
     */
    protected function getCachedTaxCode($cacheKey)
    {
        if (!array_key_exists($cacheKey, $this->taxCodes)) {
            return false;
        }

        return $this->taxCodes[$cacheKey];
    }
}
